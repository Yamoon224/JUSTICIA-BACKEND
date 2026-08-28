<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>{{ $titre }}</title>
<style>
    body { font-family: "DejaVu Sans", sans-serif; font-size: 11px; color: #1a1a1a; }
    h1 { font-size: 16px; margin: 0 0 2px; }
    .eyebrow { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #7a1f1f; margin-bottom: 6px; }
    .meta { color: #555; font-size: 10px; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    th, td { padding: 4px 6px; border: 1px solid #ccc; text-align: left; vertical-align: top; }
    .contenu { border: 1px solid #ccc; padding: 10px; margin-top: 10px; }
    .contenu p { margin: 0 0 8px; }
    .contenu p:last-child { margin-bottom: 0; }
    .contenu ul, .contenu ol { margin: 0 0 8px 18px; padding: 0; }
    .contenu li { margin-bottom: 2px; }
    .contenu strong { font-weight: bold; }
    .contenu em { font-style: italic; }
    .contenu s { text-decoration: line-through; }
    .contenu blockquote { margin: 0 0 8px; padding-left: 10px; border-left: 2px solid #ccc; color: #555; }
    .footer { position: fixed; bottom: -25px; left: 0; right: 0; font-size: 8px; color: #888; border-top: 1px solid #ccc; padding-top: 4px; }
</style>
</head>
<body>
<div class="eyebrow">{{ $eyebrow }}</div>
<h1>{{ $titre }}</h1>
<div class="meta">Édité le {{ $genereAt }}</div>

@yield('contenu')

<div class="footer">
    JUSTICIA — document généré électroniquement — empreinte numérique : {{ $empreinte }}
</div>
</body>
</html>
