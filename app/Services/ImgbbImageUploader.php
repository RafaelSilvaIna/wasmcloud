<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class ImgbbImageUploader
{
    public function upload(UploadedFile $file, string $context): string
    {
        $apiKey = config('services.imgbb.key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('Servico de upload indisponivel. Configure a chave do ImgBB no servidor.');
        }

        $extension = $file->extension() ?: 'jpg';
        $filename = Str::slug($context).'-'.Str::uuid().'.'.$extension;

        try {
            $response = Http::timeout(25)
                ->asMultipart()
                ->attach('image', file_get_contents($file->getRealPath()), $filename)
                ->post(config('services.imgbb.endpoint'), [
                    'key' => $apiKey,
                    'name' => pathinfo($filename, PATHINFO_FILENAME),
                ])
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            report($exception);

            throw new RuntimeException('Nao foi possivel enviar a imagem agora. Tente novamente em alguns instantes.');
        }

        $url = data_get($response, 'data.display_url') ?: data_get($response, 'data.url');

        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('O provedor de imagem retornou uma resposta inesperada.');
        }

        return $url;
    }
}
