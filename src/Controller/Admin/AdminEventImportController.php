<?php

namespace App\Controller\Admin;

use App\Entity\Administrator;
use App\Entity\EventImport;
use App\Form\EventImportType;
use App\Repository\EventImportRepository;
use App\Service\EventImportService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/import')]
#[IsGranted('ROLE_ADMIN')]
class AdminEventImportController extends AbstractController
{
    public function __construct(
        private EventImportRepository $importRepository,
        private EventImportService $importService,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'admin_import_index')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        /** @var Administrator $admin */
        $admin = $this->getUser();

        // Super admins see all imports, regular admins see only their own
        if ($admin->isSuperAdmin()) {
            $query = $this->importRepository->createQueryBuilder('ei')
                ->leftJoin('ei.createdBy', 'cb')
                ->addSelect('cb')
                ->orderBy('ei.createdAt', 'DESC')
                ->getQuery();
        } else {
            $query = $this->importRepository->findForAdministrator($admin);
        }

        $imports = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        // Get import statistics
        $stats = $this->importRepository->getImportStats();

        return $this->render('admin/import/index.html.twig', [
            'imports' => $imports,
            'stats' => $stats,
        ]);
    }

    #[Route('/new', name: 'admin_import_new')]
    public function new(Request $request): Response
    {
        $eventImport = new EventImport();
        $form = $this->createForm(EventImportType::class, $eventImport);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Administrator $admin */
            $admin = $this->getUser();

            $uploadedFile = $form->get('file')->getData();
            if ($uploadedFile) {
                try {
                    // Parse the file and store preview data
                    $parsedData = $this->importService->parseFile($uploadedFile, $eventImport->getImportType());
                    
                    $eventImport->setFilename($uploadedFile->getClientOriginalName());
                    $eventImport->setCreatedBy($admin);
                    $eventImport->setTotalRows($parsedData['total_rows']);
                    $eventImport->setImportedData($parsedData);

                    $this->entityManager->persist($eventImport);
                    $this->entityManager->flush();

                    $this->addFlash('success', 'File uploaded and parsed successfully. Review the preview below.');
                    return $this->redirectToRoute('admin_import_preview', ['id' => $eventImport->getId()]);

                } catch (\Exception $e) {
                    $this->addFlash('error', 'Failed to parse file: ' . $e->getMessage());
                }
            }
        }

        return $this->render('admin/import/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/preview', name: 'admin_import_preview', requirements: ['id' => '\d+'])]
    public function preview(EventImport $eventImport): Response
    {
        $this->checkImportAccess($eventImport);

        if ($eventImport->getStatus() !== 'pending') {
            $this->addFlash('error', 'Import preview is only available for pending imports.');
            return $this->redirectToRoute('admin_import_show', ['id' => $eventImport->getId()]);
        }

        $importedData = $eventImport->getImportedData();
        if (!$importedData) {
            $this->addFlash('error', 'No preview data available for this import.');
            return $this->redirectToRoute('admin_import_index');
        }

        return $this->render('admin/import/preview.html.twig', [
            'import' => $eventImport,
            'data' => $importedData,
        ]);
    }

    #[Route('/{id}/execute', name: 'admin_import_execute', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function execute(EventImport $eventImport): Response
    {
        $this->checkImportAccess($eventImport);

        if ($eventImport->getStatus() !== 'pending') {
            $this->addFlash('error', 'Only pending imports can be executed.');
            return $this->redirectToRoute('admin_import_show', ['id' => $eventImport->getId()]);
        }

        try {
            // Process the import in the background (for now, we'll do it synchronously)
            $this->importService->processImport($eventImport);

            if ($eventImport->getStatus() === 'completed') {
                $this->addFlash('success', 'Import completed successfully!');
            } else {
                $this->addFlash('warning', 'Import completed with some errors. Check the details below.');
            }

        } catch (\Exception $e) {
            $this->addFlash('error', 'Import failed: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_import_show', ['id' => $eventImport->getId()]);
    }

    #[Route('/{id}', name: 'admin_import_show', requirements: ['id' => '\d+'])]
    public function show(EventImport $eventImport): Response
    {
        $this->checkImportAccess($eventImport);

        return $this->render('admin/import/show.html.twig', [
            'import' => $eventImport,
        ]);
    }

    #[Route('/{id}/cancel', name: 'admin_import_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(EventImport $eventImport): Response
    {
        $this->checkImportAccess($eventImport);

        if (!in_array($eventImport->getStatus(), ['pending', 'processing'])) {
            $this->addFlash('error', 'Cannot cancel this import.');
            return $this->redirectToRoute('admin_import_show', ['id' => $eventImport->getId()]);
        }

        $eventImport->setStatus('cancelled');
        $eventImport->addError('Import cancelled by user.');
        $this->entityManager->flush();

        $this->addFlash('success', 'Import has been cancelled.');
        return $this->redirectToRoute('admin_import_index');
    }

    #[Route('/{id}/delete', name: 'admin_import_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(EventImport $eventImport, Request $request): Response
    {
        $this->checkImportAccess($eventImport);

        if ($this->isCsrfTokenValid('delete-import-' . $eventImport->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($eventImport);
            $this->entityManager->flush();

            $this->addFlash('success', 'Import record deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_import_index');
    }

    #[Route('/template/{type}', name: 'admin_import_template')]
    public function downloadTemplate(string $type): Response
    {
        $validTypes = ['complete', 'events_only', 'attendees_only', 'agenda_only', 'presenters_only'];
        if (!in_array($type, $validTypes)) {
            throw $this->createNotFoundException('Invalid template type');
        }

        try {
            $csvContent = $this->importService->generateTemplate($type);
            
            $response = new Response($csvContent);
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $type . '_template.csv"');
            
            return $response;
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to generate template: ' . $e->getMessage());
            return $this->redirectToRoute('admin_import_new');
        }
    }

    #[Route('/cleanup', name: 'admin_import_cleanup', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function cleanup(): Response
    {
        try {
            $deletedCount = $this->importRepository->cleanupOldImports(30);
            $this->addFlash('success', "Cleaned up {$deletedCount} old import records.");
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to cleanup imports: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_import_index');
    }

    #[Route('/bulk-delete', name: 'admin_import_bulk_delete', methods: ['POST'])]
    public function bulkDelete(Request $request): Response
    {
        $importIds = $request->request->all('import_ids');
        
        if (empty($importIds)) {
            $this->addFlash('error', 'No imports selected.');
            return $this->redirectToRoute('admin_import_index');
        }

        /** @var Administrator $admin */
        $admin = $this->getUser();

        $queryBuilder = $this->importRepository->createQueryBuilder('ei')
            ->where('ei.id IN (:ids)')
            ->setParameter('ids', $importIds);

        // Regular admins can only delete their own imports
        if (!$admin->isSuperAdmin()) {
            $queryBuilder->andWhere('ei.createdBy = :admin')
                         ->setParameter('admin', $admin);
        }

        $imports = $queryBuilder->getQuery()->getResult();
        
        $count = 0;
        foreach ($imports as $import) {
            // Only allow deletion of completed or failed imports
            if (in_array($import->getStatus(), ['completed', 'failed', 'cancelled'])) {
                $this->entityManager->remove($import);
                $count++;
            }
        }

        if ($count > 0) {
            $this->entityManager->flush();
            $this->addFlash('success', "Deleted {$count} import record(s).");
        } else {
            $this->addFlash('warning', 'No imports were deleted. Only completed, failed, or cancelled imports can be deleted.');
        }

        return $this->redirectToRoute('admin_import_index');
    }

    private function checkImportAccess(EventImport $eventImport): void
    {
        /** @var Administrator $admin */
        $admin = $this->getUser();

        // Super admins can access all imports
        if ($admin->isSuperAdmin()) {
            return;
        }

        // Regular admins can only access their own imports
        if ($eventImport->getCreatedBy() !== $admin) {
            throw $this->createAccessDeniedException('You do not have permission to access this import.');
        }
    }
}