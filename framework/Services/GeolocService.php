<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * Service GeolocService - Géolocalisation IP
 * 
 * Utilise API gratuite ip-api.com (45 req/min)
 * Récupère: pays, ville, lat/long, ISP, timezone
 * 
 * Alternative payante: ipinfo.io, ipgeolocation.io
 */
class GeolocService
{
    private const API_ENDPOINT = 'http://ip-api.com/json/';
    private array $cache = [];
    
    /**
     * Géolocaliser une IP
     */
    public function locate(string $ip): ?array
    {
        // Vérifier cache
        if (isset($this->cache[$ip])) {
            return $this->cache[$ip];
        }
        
        // IPs locales = pas de géoloc
        if ($this->isLocalIP($ip)) {
            return $this->getLocalData();
        }
        
        try {
            $url = self::API_ENDPOINT . $ip . '?fields=status,country,countryCode,city,lat,lon,isp,timezone';
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                return null;
            }
            
            $data = json_decode($response, true);
            
            if (!$data || $data['status'] !== 'success') {
                return null;
            }
            
            $result = [
                'country_code' => $data['countryCode'] ?? null,
                'country_name' => $data['country'] ?? null,
                'city' => $data['city'] ?? null,
                'latitude' => $data['lat'] ?? null,
                'longitude' => $data['lon'] ?? null,
                'isp' => $data['isp'] ?? null,
                'timezone' => $data['timezone'] ?? null,
            ];
            
            // Mettre en cache
            $this->cache[$ip] = $result;
            
            return $result;
            
        } catch (\Exception $e) {
            error_log("Geoloc API error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Vérifier si IP locale
     */
    private function isLocalIP(string $ip): bool
    {
        // IPv4 locales
        $localRanges = [
            '127.0.0.0/8',      // Loopback
            '10.0.0.0/8',       // Private
            '172.16.0.0/12',    // Private
            '192.168.0.0/16',   // Private
            '169.254.0.0/16',   // Link-local
        ];
        
        foreach ($localRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }
        
        // IPv6 locale
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if (str_starts_with($ip, '::1') || str_starts_with($ip, 'fe80:')) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Vérifier si IP dans range
     */
    private function ipInRange(string $ip, string $range): bool
    {
        list($subnet, $mask) = explode('/', $range);
        
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);
        
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
    
    /**
     * Données pour IP locale
     */
    private function getLocalData(): array
    {
        return [
            'country_code' => 'XX',
            'country_name' => 'Local',
            'city' => 'Localhost',
            'latitude' => null,
            'longitude' => null,
            'isp' => 'Local Network',
            'timezone' => date_default_timezone_get(),
        ];
    }
    
    /**
     * Obtenir IP du client
     */
    public static function getClientIP(): string
    {
        // Vérifier proxies / load balancers
        $headers = [
            'HTTP_CF_CONNECTING_IP',    // Cloudflare
            'HTTP_X_FORWARDED_FOR',     // Standard proxy
            'HTTP_X_REAL_IP',           // Nginx proxy
            'HTTP_CLIENT_IP',
        ];
        
        foreach ($headers as $header) {
            if (isset($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Géolocaliser l'IP actuelle
     */
    public function locateCurrent(): ?array
    {
        return $this->locate(self::getClientIP());
    }
}
