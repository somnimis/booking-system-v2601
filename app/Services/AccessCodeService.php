<?php

namespace App\Services;

use App\Models\RequisitionForm;

class AccessCodeService
{
    private const PREFIX = 'REQ-';
    private const RANDOM_LENGTH = 6; // Total 10 chars: REQ- (4) + 6 random = 10
    
    /**
     * Generate a unique access code with REQ- prefix
     * Format: REQ-XXXXXX (total 10 characters)
     * 
     * @return string
     */
    public function generateUniqueAccessCode(): string
    {
        do {
            $code = $this->generateFormattedCode();
        } while ($this->codeExists($code));
        
        return $code;
    }
    
    /**
     * Generate formatted code with prefix
     * 
     * @return string
     */
    private function generateFormattedCode(): string
    {
        $randomPart = $this->generateRandomString(self::RANDOM_LENGTH);
        return self::PREFIX . $randomPart;
    }
    
    /**
     * Generate random alphanumeric string
     * 
     * @param int $length
     * @return string
     */
    private function generateRandomString(int $length): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $randomString;
    }
    
    /**
     * Check if code already exists in database
     * 
     * @param string $code
     * @return bool
     */
    private function codeExists(string $code): bool
    {
        return RequisitionForm::where('access_code', $code)->exists();
    }
}