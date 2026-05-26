<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class LinodeApiService
{
    private ?string $token = null;

    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function testConnection(): array
    {
        return $this->get('/account');
    }

    public function getAccount(): array
    {
        return $this->get('/account');
    }

    public function listInstances(): array
    {
        $response = $this->get('/linode/instances');
        return $response['data'] ?? [];
    }

    public function getInstance(int $linodeId): array
    {
        return $this->get('/linode/instances/' . $linodeId);
    }

    public function getType(string $typeId): array
    {
        return $this->get('/linode/types/' . $typeId);
    }

    public function createInstance(
        string $type,
        string $region,
        string $label,
        string $rootPass,
        string $image = null
    ): array {
        $payload = [
            'type' => $type,
            'region' => $region,
            'label' => $label,
            'root_pass' => $rootPass,
            'image' => $image ?? config('linode.default_image'),
            'booted' => true,
        ];

        return $this->post('/linode/instances', $payload);
    }

    public function deleteInstance(int $linodeId): void
    {
        $this->delete('/linode/instances/' . $linodeId);
    }

    public function bootInstance(int $linodeId): void
    {
        $this->postEmpty('/linode/instances/' . $linodeId . '/boot');
    }

    public function shutdownInstance(int $linodeId): void
    {
        $this->postEmpty('/linode/instances/' . $linodeId . '/shutdown');
    }

    public function rebootInstance(int $linodeId): void
    {
        $this->postEmpty('/linode/instances/' . $linodeId . '/reboot');
    }

    /**
     * Đổi root password (VPS phải đang OFF).
     * Linode yêu cầu shutdown trước khi đổi pass.
     */
    public function resetPassword(int $linodeId, string $newPassword): void
    {
        $response = Http::withToken($this->requireToken())
            ->acceptJson()
            ->timeout(60)
            ->post(config('linode.api_base') . '/linode/instances/' . $linodeId . '/password', [
                'root_pass' => $newPassword,
            ]);

        // 200 OK hoặc 204 No Content đều là thành công
        if (!$response->successful()) {
            throw new RuntimeException($this->errorMessage($response));
        }
    }

    /**
     * Rebuild VPS: cài lại OS sạch, giữ IP, mất toàn bộ data.
     */
    public function rebuildInstance(int $linodeId, string $image, string $rootPass): array
    {
        return $this->post('/linode/instances/' . $linodeId . '/rebuild', [
            'image'     => $image,
            'root_pass' => $rootPass,
            'booted'    => true,
        ]);
    }

    private function get(string $path): array
    {
        $response = Http::withToken($this->requireToken())
            ->acceptJson()
            ->timeout(30)
            ->get(config('linode.api_base') . $path);

        return $this->parseResponse($response);
    }

    /**
     * POST với body {} rỗng (boot, shutdown, reboot).
     * Linode yêu cầu JSON object, không chấp nhận [] hay body trống.
     */
    private function postEmpty(string $path): void
    {
        $response = Http::withToken($this->requireToken())
            ->acceptJson()
            ->contentType('application/json')
            ->timeout(60)
            ->withBody('{}', 'application/json')
            ->post(config('linode.api_base') . $path);

        if (!$response->successful()) {
            throw new RuntimeException($this->errorMessage($response));
        }
    }

    private function post(string $path, array $data): array
    {
        $response = Http::withToken($this->requireToken())
            ->acceptJson()
            ->timeout(60)
            ->post(config('linode.api_base') . $path, $data);

        return $this->parseResponse($response);
    }

    private function delete(string $path): void
    {
        $response = Http::withToken($this->requireToken())
            ->acceptJson()
            ->timeout(60)
            ->delete(config('linode.api_base') . $path);

        if (!$response->successful() && $response->status() !== 404) {
            throw new RuntimeException($this->errorMessage($response));
        }
    }

    private function parseResponse($response): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        throw new RuntimeException($this->errorMessage($response));
    }

    private function errorMessage($response): string
    {
        $json = $response->json();
        if (isset($json['errors'][0]['reason'])) {
            return $json['errors'][0]['reason'];
        }

        return 'Linode API error (HTTP ' . $response->status() . ')';
    }

    private function requireToken(): string
    {
        if (!$this->token) {
            throw new RuntimeException('Linode API token chưa được cấu hình.');
        }

        return $this->token;
    }
}
