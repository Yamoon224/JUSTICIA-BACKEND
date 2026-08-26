@extends('pdf.layout')

@section('contenu')
<table>
    <tr><th>Personne</th><td>{{ $personne->nomAffichage() }}</td></tr>
    <tr><th>Identifiant unique</th><td>{{ $personne->identifiant_unique }}</td></tr>
    <tr><th>Motif de délivrance</th><td>{{ $motif }}</td></tr>
</table>

@if ($condamnations->isEmpty())
    <p><strong>Néant</strong> — aucune condamnation ne figure à ce bulletin.</p>
@else
    <table>
        <thead>
            <tr>
                <th>Affaire</th>
                <th>Juridiction</th>
                <th>Infraction</th>
                <th>Peine</th>
                <th>Date</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($condamnations as $condamnation)
                <tr>
                    <td>{{ $condamnation->numero_affaire }}</td>
                    <td>{{ $condamnation->juridiction_nom }}</td>
                    <td>{{ $condamnation->infraction_libelle }}</td>
                    <td>{{ $condamnation->peine_principale }}{{ $condamnation->sursis ? ' (sursis)' : '' }}</td>
                    <td>{{ $condamnation->condamnee_at->format('d/m/Y') }}</td>
                    <td>{{ ucfirst($condamnation->statut) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
@endsection
