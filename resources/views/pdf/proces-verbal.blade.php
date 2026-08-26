@extends('pdf.layout')

@section('contenu')
<table>
    <tr>
        <th>Cote</th><td>{{ $pv->cote }}</td>
        <th>Type</th><td>{{ ucfirst(str_replace('_', ' ', $pv->type)) }}</td>
    </tr>
    <tr>
        <th>Affaire</th><td colspan="3">{{ $pv->affaire->numero_affaire }}</td>
    </tr>
    <tr>
        <th>Rédigé par</th><td>{{ $pv->redacteur->matricule ?? '—' }}</td>
        <th>Statut</th>
        <td>
            @if ($pv->estSigne())
                Signé le {{ $pv->signe_at->format('d/m/Y H:i') }}
            @else
                Non signé — projet, sans valeur probante
            @endif
        </td>
    </tr>
</table>

<div class="contenu">{{ $pv->contenu }}</div>
@endsection
