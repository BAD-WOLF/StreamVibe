<?php

namespace App\ApiResource\Image;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\API\Shared\ImageController;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use App\ApiResource\Image\Model\ImageOutput;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/movies/image/{size}/{endpoint}',
            controller: ImageController::class,
            openapi: new Operation(
                parameters: [
                    (new Parameter(
                        name: '_locale',
                        in: 'path',
                        required: true,
                        schema: [
                            'type' => 'string',
                            'default' => 'pt_BR',
                        ]
                    )),
                    (new Parameter(
                        name: 'endpoint',
                        in: 'path',
                        description: 'Identificador do recurso de imagem',
                        required: true,
                        schema: ['type' => 'string',]
                    )),
                    (new Parameter(
                        name: 'size',
                        in: 'path',
                        description: 'Tamanho da imagem (opcional)',
                        required: false,
                        schema: [
                            'type' => 'string',
                            'enum' => [
                                'w92','w154','w185','w342','w500','w780',
                                'w300','w780','w45','w185','h632','original',
                            ],
                            'default' => 'original',
                            'required' => true,
                        ]
                    )),
                ],
            ),
            output: ImageOutput::class,
            name: 'get_api_movies_img'),
    ]
)]
class ImageEntryPoint {

}