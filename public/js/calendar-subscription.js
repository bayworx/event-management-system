/**
 * Calendar Subscription Helper Functions
 * Provides clipboard functionality and user feedback for calendar feed URLs
 */

function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        // Use modern clipboard API
        navigator.clipboard.writeText(text).then(function() {
            showCopySuccess();
        }, function() {
            fallbackCopyTextToClipboard(text);
        });
    } else {
        // Fallback for older browsers
        fallbackCopyTextToClipboard(text);
    }
}

function fallbackCopyTextToClipboard(text) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    textArea.style.opacity = "0";
    
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showCopySuccess();
        } else {
            showCopyError();
        }
    } catch (err) {
        showCopyError();
    }
    
    document.body.removeChild(textArea);
}

function showCopySuccess() {
    // Create temporary success message
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    alert.style.minWidth = '300px';
    alert.innerHTML = `
        <i class="bi bi-check-circle"></i> Calendar feed URL copied to clipboard!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(alert);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.classList.remove('show');
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 150);
        }
    }, 3000);
}

function showCopyError() {
    // Create temporary error message
    const alert = document.createElement('div');
    alert.className = 'alert alert-warning alert-dismissible fade show position-fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    alert.style.minWidth = '350px';
    alert.innerHTML = `
        <i class="bi bi-exclamation-triangle"></i> Unable to copy automatically. Please copy the URL manually.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(alert);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.classList.remove('show');
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 150);
        }
    }, 5000);
}

// Show calendar subscription instructions modal (optional)
function showCalendarInstructions() {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'calendarInstructionsModal';
    modal.tabIndex = -1;
    modal.setAttribute('aria-labelledby', 'calendarInstructionsModalLabel');
    modal.setAttribute('aria-hidden', 'true');
    
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="calendarInstructionsModalLabel">
                        <i class="bi bi-calendar-plus"></i> How to Subscribe to Calendar
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="bi bi-google"></i> Google Calendar</h6>
                            <ol class="small">
                                <li>Open Google Calendar</li>
                                <li>Click the "+" next to "Other calendars"</li>
                                <li>Select "From URL"</li>
                                <li>Paste the calendar feed URL</li>
                                <li>Click "Add calendar"</li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="bi bi-microsoft"></i> Outlook</h6>
                            <ol class="small">
                                <li>Open Outlook Calendar</li>
                                <li>Click "Add calendar" → "From internet"</li>
                                <li>Paste the calendar feed URL</li>
                                <li>Give the calendar a name</li>
                                <li>Click "Import"</li>
                            </ol>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h6><i class="bi bi-apple"></i> Apple Calendar</h6>
                            <ol class="small">
                                <li>Open Calendar app</li>
                                <li>Go to File → New Calendar Subscription</li>
                                <li>Paste the calendar feed URL</li>
                                <li>Choose refresh frequency</li>
                                <li>Click "Subscribe"</li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info small">
                                <i class="bi bi-info-circle"></i>
                                <strong>Note:</strong> The calendar will automatically update when new events are added or existing events are modified.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
    
    // Clean up when modal is hidden
    modal.addEventListener('hidden.bs.modal', function() {
        document.body.removeChild(modal);
    });
}