<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $recipe->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            color: #000000;
            background: #ffffff;
            padding: 20px;
            line-height: 1.6;
        }

        .header {
            margin-bottom: 30px;
        }

        h1 {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #000000;
        }

        .author {
            color: #666666;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .image-container {
            margin-bottom: 20px;
            text-align: center;
        }

        .image-container img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }

        .category-badge {
            display: inline-block;
            background-color: #3b82f6;
            color: #ffffff;
            padding: 5px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-item {
            flex: 1;
        }

        .stat-label {
            font-size: 12px;
            color: #666666;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #000000;
        }

        h2 {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 10px;
            margin-top: 30px;
            color: #000000;
        }

        .content {
            line-height: 1.6;
            color: #000000;
            white-space: pre-wrap;
            margin-bottom: 20px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $recipe->name ?? 'Receita sem nome' }}</h1>
        @if($recipe->user)
            <div class="author">Por: {{ $recipe->user->name }}</div>
        @endif
    </div>

    @if($imageUrl)
        <div class="image-container">
            <img src="{{ $imageUrl }}" alt="{{ $recipe->name }}" style="max-width: 100%; height: auto;" />
        </div>
    @endif

    @if($recipe->category)
        <div class="category-badge">{{ $recipe->category->name }}</div>
    @endif

    <div class="stats">
        @if($recipe->prep_time_minutes)
            <div class="stat-item">
                <div class="stat-label">Tempo de preparo</div>
                <div class="stat-value">{{ $prepTime }}</div>
            </div>
        @endif
        @if($recipe->servings)
            <div class="stat-item">
                <div class="stat-label">Porções</div>
                <div class="stat-value">{{ $recipe->servings }}</div>
            </div>
        @endif
    </div>

    @if($ingredients)
        <h2>Ingredientes</h2>
        <div class="content">{{ $ingredients }}</div>
    @endif

    <h2>Modo de preparo</h2>
    <div class="content">{{ $instructions }}</div>
</body>
</html>

