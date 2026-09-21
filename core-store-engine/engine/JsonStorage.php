<?php
declare(strict_types=1);

namespace CoreStore\Engine;

class JsonStorage
{
   private string $filePath;

   public function __construct(string $filePath)
   {
       $this->filePath = $filePath;
       $this->ensureFileExists();
   }

   private function ensureFileExists(): void
   {
       if (!file_exists($this->filePath)) {
           $dir = dirname($this->filePath);
           if (!is_dir($dir)) {
               mkdir($dir, 0755, true);
           }
           file_put_contents($this->filePath, json_encode([], JSON_PRETTY_PRINT));
       }
   }

   public function read(): array
   {
       if (!file_exists($this->filePath)) return [];
       $fp = fopen($this->filePath, 'rb');
       if (!$fp) return [];

       flock($fp, LOCK_SH);
       $content = stream_get_contents($fp);
       flock($fp, LOCK_UN);
       fclose($fp);

       return json_decode($content ?: '[]', true) ?? [];
   }

   public function write(array $data): bool
   {
       $payload = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
       $tempFile = $this->filePath . '.tmp.' . bin2hex(random_bytes(6));

       $fp = fopen($tempFile, 'wb');
       if (!$fp) return false;

       flock($fp, LOCK_EX);
       fwrite($fp, $payload);
       fflush($fp);
       flock($fp, LOCK_UN);
       fclose($fp);

       return rename($tempFile, $this->filePath);
   }
}
