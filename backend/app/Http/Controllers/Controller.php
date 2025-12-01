<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Recipes API",
    description: "API para gerenciamento de receitas culinárias",
    contact: new OA\Contact(
        name: "API Support",
        email: "support@example.com"
    )
)]
#[OA\Server(
    url: "/api",
    description: "API Server"
)]
#[OA\SecurityScheme(
    securityScheme: "sanctum",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Laravel Sanctum token authentication"
)]
#[OA\Schema(
    schema: "Recipe",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "user_id", type: "integer", example: 1),
        new OA\Property(
            property: "user",
            type: "object",
            nullable: true,
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "name", type: "string", example: "John Doe"),
            ]
        ),
        new OA\Property(property: "category_id", type: "integer", nullable: true, example: 1),
        new OA\Property(
            property: "category",
            type: "object",
            nullable: true,
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "name", type: "string", example: "Sobremesas"),
                new OA\Property(property: "created_at", type: "string", format: "date-time"),
                new OA\Property(property: "updated_at", type: "string", format: "date-time"),
            ]
        ),
        new OA\Property(property: "name", type: "string", nullable: true, example: "Bolo de Chocolate"),
        new OA\Property(property: "prep_time_minutes", type: "integer", nullable: true, example: 30),
        new OA\Property(property: "servings", type: "integer", nullable: true, example: 8),
        new OA\Property(property: "image", type: "string", format: "url", nullable: true, example: "https://example.com/image.jpg"),
        new OA\Property(property: "instructions", type: "string", example: "Misture todos os ingredientes..."),
        new OA\Property(property: "ingredients", type: "string", nullable: true, example: "2 xícaras de farinha, 3 ovos..."),
        new OA\Property(
            property: "comments",
            type: "array",
            nullable: true,
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "comment", type: "string", example: "Delicioso!"),
                    new OA\Property(
                        property: "user",
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "name", type: "string", example: "John Doe"),
                        ]
                    ),
                    new OA\Property(property: "created_at", type: "string", format: "date-time"),
                ]
            )
        ),
        new OA\Property(property: "comments_count", type: "integer", nullable: true, example: 5),
        new OA\Property(property: "ratings_count", type: "integer", nullable: true, example: 10),
        new OA\Property(property: "average_rating", type: "number", format: "float", nullable: true, example: 4.5),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
class Controller
{
    //
}
