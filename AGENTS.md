# Atendly — notas para agentes

- Laravel 13 + Blade + Sass + JS puro (sem Tailwind, sem framework JS). UI e mensagens em pt-BR; código em inglês.
- Models usam atributos `#[Fillable]`/`#[Hidden]` (padrão do Laravel 13).
- Regras de negócio do chamado ficam em `app/Services/TicketService.php`. Não altere timestamps do ciclo (`acknowledged_at`, `first_response_at`, `resolved_at`, `closed_at`) direto nos controllers.
- Busca: `KnowledgeSearch` usa FULLTEXT no MySQL e LIKE nos demais drivers. Os índices FULLTEXT só são criados em MySQL/MariaDB.
- Dados sintéticos sempre com `is_synthetic = true`. Nunca apresente indicadores sintéticos como evidência real.
- Entrevistas não podem conter dados identificáveis (`App\Rules\NoPersonalData`).
- Testes: `php artisan test` (SQLite em memória). Estilo: `vendor/bin/pint`.
- Composer nesta máquina: se travar em "Loading composer repositories", use `--no-cache`.
