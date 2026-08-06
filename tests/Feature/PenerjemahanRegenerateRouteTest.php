<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PenerjemahanRegenerateRouteTest extends TestCase
{
    use WithoutMiddleware;

    /**
     * Pastikan tidak ada route regenerate PDF penerjemahan yang terekspos.
     */
    public function test_regenerate_route_is_not_registered(): void
    {
        $this->assertFalse(Route::has('penerjemahan.regenerate'));
    }

    /**
     * Hitting URL lama /penerjemahan/{id}/pdf/regenerate harus 404.
     */
    public function test_regenerate_url_returns_404(): void
    {
        $this->withoutExceptionHandling();
        $this->expectException(NotFoundHttpException::class);

        $this->get('/penerjemahan/1/pdf/regenerate');
    }
}
