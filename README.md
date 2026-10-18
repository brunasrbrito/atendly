# Atendly

Protótipo de apoio ao suporte de TI que recebe chamados de um sistema de terceiros. Ele ataca as duas dificuldades levantadas no diagnóstico inicial:

1. **Chamados percebidos com atraso.** A fila avisa quando chega chamado novo (aviso na tela, som, notificação do navegador e contador na aba) e mede o **tempo até a percepção**, ou seja, o tempo entre a abertura e o momento em que um técnico assume o chamado.
2. **Recuperação de soluções anteriores.** Hoje a equipe procura por palavra-chave no WhatsApp ou pergunta a colegas. No Atendly há uma busca por relevância (MySQL FULLTEXT) na base de conhecimento e nas soluções de chamados encerrados, e a tela de atendimento já sugere soluções parecidas.

Também traz apoio ao diagnóstico: um painel de indicadores e o registro de **entrevistas sem identificação**.

> **Dados sintéticos.** Não há autorização para usar chamados reais. Toda a base gerada pelo seeder tem `is_synthetic = true`, aparece com o selo "Sintético" e a interface exibe um aviso permanente. Indicadores calculados sobre esses dados testam o protótipo e as hipóteses. Eles **não** são evidência do funcionamento real do suporte.

## Stack

- Laravel 13 (PHP 8.3+), Blade
- MySQL 8 (índices FULLTEXT)
- Sass (`resources/scss`), compilado pelo Vite
- JavaScript (ES modules, sem framework) e Chart.js no painel

## Instalação

```bash
composer install
npm install
cp .env.example .env        # ajuste DB_* para o seu MySQL
php artisan key:generate
```

Crie o banco (`utf8mb4_unicode_ci` deixa a busca insensível a acentos):

```sql
CREATE DATABASE atendly CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan migrate --seed  # estrutura + base sintética (~500 chamados em 90 dias)
php artisan storage:link    # anexos (imagens e áudios)
npm run build               # ou: npm run dev
php artisan serve
```

Acesso local: `admin@atendly.test` ou `tecnico1@atendly.test` … `tecnico4@atendly.test`, com a senha definida em `database/seeders` (apenas para desenvolvimento).

### Esqueci a senha

No login, "Esqueceu a senha?" envia um link de redefinição, válido por 60 minutos e de uso único. A mensagem é a mesma exista ou não a conta, e após a troca as outras sessões do usuário são encerradas. Em ambiente local (`MAIL_MAILER=log`), o e-mail não é enviado: ele aparece em `storage/logs/laravel.log`. Para enviar de verdade, configure `MAIL_MAILER=smtp` e os campos `MAIL_*` no `.env`.

## Funcionalidades

| Área | O que faz |
|---|---|
| **Fila de chamados** (`/chamados`) | Os não assumidos ficam no topo e o tempo de espera é atualizado ao vivo. Fica vermelho quando passa da meta de resposta da categoria. Um resumo mostra quantos aguardam e há quanto tempo. "Assumir o próximo" pega o chamado que espera há mais tempo. O botão "Assumir" registra a percepção. Atalho `/` foca a busca. |
| **Alerta de novos chamados** | Consulta `/fila/novidades` a cada `ATENDLY_POLL_SECONDS`. Quando chega chamado: aviso na tela, som (pode ser desligado), notificação do navegador e contador `(N)` no título. |
| **Atendimento** (`/chamados/{id}`) | Descrição, anexos (imagem, áudio, arquivo), histórico de interações, resposta ou nota interna, gravação de áudio pelo microfone, categorização, técnico, status e solução. Para resolver ou fechar é obrigatório descrever a solução. |
| **Encerramento** | "Resolver chamado" abre um diálogo com status final (Resolvido ou Fechado) e a solução, que é obrigatória. Chamado resolvido ou fechado fica **somente leitura**, regra aplicada no back-end. Se o problema voltar, "Abrir novo chamado relacionado" copia solicitante e categoria e registra o vínculo (`related_ticket_id`). |
| **Soluções semelhantes** | Painel lateral com artigos e chamados encerrados parecidos. "Aplicar neste chamado" vincula o artigo (registra o reuso) e preenche a solução. |
| **Busca** (`/busca`) | Busca unificada com os termos destacados. Chamados com a mesma solução viram um único resultado, com o número de ocorrências. |
| **Base de conhecimento** | Artigos com problema, solução e palavras-chave. "Transformar em artigo" parte de um chamado resolvido. A página avisa quantas soluções ainda estão só no histórico de chamados. |
| **Indicadores** (`/painel`) | Tempo até percepção (mediana, média e p90), % acima da meta, taxa de reuso de conhecimento, percepção por hora e por dia da semana, volume diário, categorias e problemas mais recorrentes. Filtro por período e por origem dos dados. |
| **Entrevistas** (`/entrevistas`) | Código (E01…), perfil genérico, temas e anotações. Bloqueia e-mail, telefone e CPF, exige registro de consentimento e exporta CSV para análise. |

## Integração com o sistema de terceiros

O sistema de origem envia cada chamado novo para o webhook:

```http
POST /api/integracao/chamados
Authorization: Bearer <ATENDLY_INTEGRATION_TOKEN>
Content-Type: application/json

{
  "external_id": "12345",
  "title": "Impressora offline",
  "description": "A impressora do RH não aparece.",
  "category": "impressoras",
  "priority": "alta",
  "opened_at": "2026-10-18T09:12:00-03:00",
  "requester": { "external_id": "SOL-9", "name": "Solicitante 9", "department": "RH" }
}
```

- Responde `201` ao criar e `200` se o `external_id` já existe (reenvio idempotente).
- `opened_at` é o horário de abertura **na origem**. É a partir dele que o atraso de percepção é medido.

Para demonstrar sem integração real, simule chegadas (dados sintéticos):

```bash
php artisan atendly:simular            # 1 chamado
php artisan atendly:simular 5 --intervalo=20
```

## Configuração (`.env`)

| Variável | Padrão | Uso |
|---|---|---|
| `ATENDLY_INTEGRATION_TOKEN` | vazio (webhook desativado) | Token exigido pelo webhook |
| `ATENDLY_POLL_SECONDS` | 15 | Intervalo de verificação da fila |
| `ATENDLY_ACK_ALERT_MINUTES` | 15 | Meta de percepção para chamados sem categoria |
| `APP_TIMEZONE` | America/Sao_Paulo | Fuso usado nos horários |

As metas de resposta e de resolução por categoria ficam na tabela `categories`.

## Estrutura

```
app/
  Enums/                 TicketStatus, TicketPriority, UserRole
  Services/
    TicketService.php    ciclo de vida: receber, assumir, interagir, encerrar, vincular artigo
    KnowledgeSearch.php  busca FULLTEXT (MySQL) com fallback LIKE (SQLite nos testes)
    MetricsService.php   indicadores do diagnóstico
  Support/
    SyntheticTicketGenerator.php  gerador de chamados sintéticos
  Rules/NoPersonalData.php        bloqueia dados identificáveis nas entrevistas
database/seeders/
  data/scenarios.php     catálogo de cenários sintéticos (categorias, problemas, soluções)
  SyntheticDataSeeder.php
resources/
  scss/                  abstracts, base (tema claro/escuro), layout, components, pages
  js/modules/            queue-notifier, waiting-timers, audio-recorder, dashboard-charts, ui
```

## Testes

```bash
php artisan test
```

Os testes usam SQLite em memória (extensão `pdo_sqlite` necessária). Nesse caso a busca usa o fallback com `LIKE`, então o ranqueamento FULLTEXT só é exercitado com MySQL.

## Hipóteses embutidas na base sintética

O seeder gera atrasos de percepção maiores no almoço, fora do expediente e no fim de semana, e repete alguns problemas com mais frequência (senha, impressora, VPN, ERP). Essas são **hipóteses a investigar** nas entrevistas, não constatações. Ajuste `database/seeders/data/scenarios.php` e `SyntheticDataSeeder::perceptionDelay()` conforme o diagnóstico avançar.
