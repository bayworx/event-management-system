<?php

namespace App\Service;

use App\Entity\Event;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;

class WifiQrCodeService
{
    /**
     * Generate WiFi QR code for an event
     * 
     * @param Event $event The event with WiFi information
     * @return string|null Base64 encoded PNG image or null if no WiFi info
     */
    public function generateWifiQrCode(Event $event): ?string
    {
        if (!$event->hasWifiInformation()) {
            return null;
        }

        $wifiString = $this->createWifiString(
            $event->getWifiSsid(),
            $event->getWifiPassword(),
            $event->getWifiSecurityType() ?? 'WPA'
        );

        $qrCode = new QrCode(
            data: $wifiString,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 400,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );

        $label = new Label(
            text: 'Scan to Connect to WiFi',
            textColor: new Color(0, 0, 0)
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode, null, $label);

        return base64_encode($result->getString());
    }

    /**
     * Generate WiFi QR code and return as data URI
     * 
     * @param Event $event The event with WiFi information
     * @return string|null Data URI for embedding in HTML or null if no WiFi info
     */
    public function generateWifiQrCodeDataUri(Event $event): ?string
    {
        $base64 = $this->generateWifiQrCode($event);
        
        if ($base64 === null) {
            return null;
        }

        return 'data:image/png;base64,' . $base64;
    }

    /**
     * Generate WiFi QR code and save to file
     * 
     * @param Event $event The event with WiFi information
     * @param string $filepath Path where to save the QR code
     * @return bool True if successful, false otherwise
     */
    public function generateAndSaveWifiQrCode(Event $event, string $filepath): bool
    {
        if (!$event->hasWifiInformation()) {
            return false;
        }

        $wifiString = $this->createWifiString(
            $event->getWifiSsid(),
            $event->getWifiPassword(),
            $event->getWifiSecurityType() ?? 'WPA'
        );

        $qrCode = new QrCode(
            data: $wifiString,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 400,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );

        $label = new Label(
            text: 'Scan to Connect to WiFi',
            textColor: new Color(0, 0, 0)
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode, null, $label);

        $result->saveToFile($filepath);

        return true;
    }

    /**
     * Create WiFi connection string in format recognized by QR code readers
     * Format: WIFI:T:<security>;S:<ssid>;P:<password>;H:<hidden>;;
     * 
     * @param string $ssid WiFi network name
     * @param string $password WiFi password
     * @param string $security Security type (WPA, WPA2, WEP, nopass)
     * @param bool $hidden Whether network is hidden
     * @return string WiFi configuration string
     */
    private function createWifiString(string $ssid, string $password, string $security = 'WPA', bool $hidden = false): string
    {
        // Escape special characters
        $ssid = $this->escapeWifiString($ssid);
        $password = $this->escapeWifiString($password);
        
        // Map security types
        $securityType = match(strtoupper($security)) {
            'WPA', 'WPA2', 'WPA/WPA2' => 'WPA',
            'WEP' => 'WEP',
            'OPEN', 'NOPASS', 'NONE' => 'nopass',
            default => 'WPA'
        };

        // For open networks, don't include password
        if ($securityType === 'nopass') {
            return sprintf('WIFI:T:%s;S:%s;H:%s;;', 
                $securityType,
                $ssid,
                $hidden ? 'true' : 'false'
            );
        }

        return sprintf('WIFI:T:%s;S:%s;P:%s;H:%s;;', 
            $securityType,
            $ssid,
            $password,
            $hidden ? 'true' : 'false'
        );
    }

    /**
     * Escape special characters in WiFi strings
     * Characters that need escaping: \ ; , " :
     * 
     * @param string $string String to escape
     * @return string Escaped string
     */
    private function escapeWifiString(string $string): string
    {
        // Escape backslashes first
        $string = str_replace('\\', '\\\\', $string);
        
        // Escape other special characters
        $specialChars = [';', ',', '"', ':'];
        foreach ($specialChars as $char) {
            $string = str_replace($char, '\\' . $char, $string);
        }
        
        return $string;
    }

    /**
     * Get WiFi information as array for display
     * 
     * @param Event $event The event with WiFi information
     * @return array|null WiFi information or null if not available
     */
    public function getWifiInformation(Event $event): ?array
    {
        if (!$event->hasWifiInformation()) {
            return null;
        }

        return [
            'ssid' => $event->getWifiSsid(),
            'password' => $event->getWifiPassword(),
            'security' => $event->getWifiSecurityType() ?? 'WPA'
        ];
    }
}
