<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\KnowledgeArticle;
use App\Models\Ticket;
use App\Services\KnowledgeSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeArticleController extends Controller
{
    public function index(Request $request, KnowledgeSearch $search): View
    {
        $q = trim((string) $request->query('q'));

        $articles = $q !== ''
            ? $search->search($q, 30)->where('type', 'artigo')->pluck('model')
            : KnowledgeArticle::with('category')->withCount('tickets')
                ->when($request->filled('categoria'), fn ($query) => $query->where('category_id', $request->integer('categoria')))
                ->orderByDesc('tickets_count')->orderBy('title')->get();

        return view('articles.index', [
            'articles' => $articles,
            'q' => $q,
            'terms' => $search->terms($q),
            'categories' => Category::orderBy('name')->get(),
            // Chamados resolvidos cuja solução ainda não virou artigo: conhecimento "preso" no histórico.
            'unpublished' => Ticket::whereNotNull('solution')->whereDoesntHave('articles')
                ->whereNotIn('id', KnowledgeArticle::whereNotNull('source_ticket_id')->select('source_ticket_id'))
                ->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $article = new KnowledgeArticle;

        // "Transformar em artigo" a partir de um chamado resolvido.
        if ($ticket = Ticket::find($request->integer('chamado'))) {
            $article->fill([
                'title' => $ticket->title,
                'problem' => $ticket->description,
                'solution' => $ticket->solution,
                'category_id' => $ticket->category_id,
                'source_ticket_id' => $ticket->id,
                'is_synthetic' => $ticket->is_synthetic,
            ]);
        }

        return view('articles.form', ['article' => $article, 'categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $article = KnowledgeArticle::create($this->validated($request) + ['author_id' => $request->user()->id]);

        if ($article->sourceTicket) {
            $article->tickets()->syncWithoutDetaching([$article->source_ticket_id => ['user_id' => $request->user()->id]]);
        }

        return redirect()->route('articles.show', $article)->with('success', 'Artigo publicado na base de conhecimento.');
    }

    public function show(KnowledgeArticle $article): View
    {
        $article->increment('views');
        $article->load(['category', 'author', 'sourceTicket'])->loadCount('tickets');

        return view('articles.show', [
            'article' => $article,
            'recentTickets' => $article->tickets()->with('technician')->latest('opened_at')->limit(10)->get(),
        ]);
    }

    public function edit(KnowledgeArticle $article): View
    {
        return view('articles.form', ['article' => $article, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(Request $request, KnowledgeArticle $article): RedirectResponse
    {
        $article->update($this->validated($request));

        return redirect()->route('articles.show', $article)->with('success', 'Artigo atualizado.');
    }

    public function destroy(Request $request, KnowledgeArticle $article): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $article->delete();

        return redirect()->route('articles.index')->with('success', 'Artigo removido.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'problem' => ['required', 'string', 'max:20000'],
            'solution' => ['required', 'string', 'max:20000'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'source_ticket_id' => ['nullable', 'exists:tickets,id'],
        ]);

        // Normaliza as palavras-chave: minúsculas, sem duplicadas.
        $data['keywords'] = collect(explode(',', (string) ($data['keywords'] ?? '')))
            ->map(fn ($k) => mb_strtolower(trim($k)))->filter()->unique()->implode(', ') ?: null;
        $data['is_synthetic'] = $request->boolean('is_synthetic');

        return $data;
    }
}
