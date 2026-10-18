<?php

namespace App\Http\Controllers;

use App\Models\Interview;
use App\Rules\NoPersonalData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InterviewController extends Controller
{
    public function index(Request $request): View
    {
        $theme = $request->query('tema');
        $interviews = Interview::orderBy('code')->get();

        return view('interviews.index', [
            'interviews' => $theme ? $interviews->filter(fn ($i) => in_array($theme, $i->themes ?? [], true)) : $interviews,
            'theme' => $theme,
            'themes' => Interview::THEMES,
            // Quantas entrevistas mencionam cada tema: apoio à codificação qualitativa.
            'themeCounts' => collect(Interview::THEMES)->map(
                fn ($label, $key) => $interviews->filter(fn ($i) => in_array($key, $i->themes ?? [], true))->count()
            ),
        ]);
    }

    public function create(): View
    {
        return view('interviews.form', [
            'interview' => new Interview(['code' => Interview::nextCode(), 'conducted_on' => now()]),
            'themes' => Interview::THEMES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $interview = Interview::create($this->validated($request) + ['created_by' => $request->user()->id]);

        return redirect()->route('interviews.show', $interview)->with('success', 'Entrevista registrada.');
    }

    public function show(Interview $interview): View
    {
        return view('interviews.show', ['interview' => $interview, 'themes' => Interview::THEMES]);
    }

    public function edit(Interview $interview): View
    {
        return view('interviews.form', ['interview' => $interview, 'themes' => Interview::THEMES]);
    }

    public function update(Request $request, Interview $interview): RedirectResponse
    {
        $interview->update($this->validated($request, $interview));

        return redirect()->route('interviews.show', $interview)->with('success', 'Entrevista atualizada.');
    }

    public function destroy(Interview $interview): RedirectResponse
    {
        $interview->delete();

        return redirect()->route('interviews.index')->with('success', 'Entrevista removida.');
    }

    public function export(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM para o Excel reconhecer UTF-8
            fputcsv($out, ['codigo', 'perfil', 'data', 'consentimento', 'temas', 'anotacoes', 'sintetico'], ';');

            foreach (Interview::orderBy('code')->cursor() as $i) {
                fputcsv($out, [
                    $i->code, $i->profile, $i->conducted_on->toDateString(), $i->consent_recorded ? 'sim' : 'nao',
                    implode('|', $i->themes ?? []), $i->notes, $i->is_synthetic ? 'sim' : 'nao',
                ], ';');
            }
            fclose($out);
        }, 'entrevistas-atendly.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Interview $interview = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'regex:/^E\d{2,3}$/', Rule::unique('interviews')->ignore($interview)],
            'profile' => ['required', 'string', 'max:255', new NoPersonalData],
            'conducted_on' => ['required', 'date', 'before_or_equal:today'],
            'consent_recorded' => ['accepted'],
            'themes' => ['array'],
            'themes.*' => [Rule::in(array_keys(Interview::THEMES))],
            'notes' => ['required', 'string', 'max:30000', new NoPersonalData],
        ], [
            'code.regex' => 'Use um código no formato E01, E02...',
            'consent_recorded.accepted' => 'Registre o consentimento do participante antes de salvar.',
        ]);

        return $data + ['themes' => [], 'is_synthetic' => $request->boolean('is_synthetic')];
    }
}
