<?php

namespace App\Services\Interfaces;

interface NotificationInterface
{
    /**
     * Mengirim notifikasi teks.
     * 
     * @param string $target Nomor HP / Email
     * @param string $message Isi pesan
     * @return bool
     */
    public function send(string $target, string $message): bool;
}
