<?php

namespace App\Controller\API\Shared;

use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\TmdbApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\ApiResource\Movies\MoviesEntryPoint;
use Symfony\Component\HttpFoundation\JsonResponse;

#[AsController]
#[Route(name: 'api_movies_img',
    defaults: [
        '_api_resource_class' => MoviesEntryPoint::class,
        '_api_operation_name' => 'api_movies_img',
    ], methods: ['GET']
)]
class ImageController extends AbstractController {
    /**
     * @param string|null                 $size
     * @param string                      $endpoint
     * @param \App\Service\TmdbApiService $tmdbApiService
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function __invoke(?string $size, string $endpoint, TmdbApiService $tmdbApiService): JsonResponse {
        $imageBinary = $tmdbApiService->tmdb_image($size, $endpoint);
        $mimeType = finfo_buffer(finfo_open(), $imageBinary, FILEINFO_MIME_TYPE);
        $base64WithPrefix = "data:$mimeType;base64,".base64_encode($imageBinary);

        return new JsonResponse(['base64' => $base64WithPrefix]);
    }
}