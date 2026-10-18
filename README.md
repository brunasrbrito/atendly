# Atendly

Protótipo de apoio ao suporte de TI que recebe chamados de um sistema de terceiros. Ele ataca as duas dificuldades levantadas no diagnóstico inicial:

1. **Chamados percebidos com atraso.** A fila avisa quando chega chamado novo (aviso na tela, som, notificação do navegador e contador na aba) e mede o **tempo até a percepção**, ou seja, o tempo entre a abertura e o momento em que um técnico assume o chamado.
2. **Recuperação de soluções anteriores.** Hoje a equipe procura por palavra-chave no WhatsApp ou pergunta a colegas. No Atendly há uma busca por relevância (MySQL FULLTEXT) na base de conhecimento e nas soluções de chamados encerrados, e a tela de atendimento já sugere soluções parecidas.

Também traz apoio ao diagnóstico: um painel de indicadores e o registro de **entrevistas sem identificação**.

> **Dados sintéticos.** Não há autorização para usar chamados reais. Toda a base gerada pelo seeder tem `is_synthetic = true`, aparece com o selo "Sintético" e a interface exibe um aviso permanente. Indicadores calculados sobre esses dados testam o protótipo e as hipóteses. Eles **não** são evidência do funcionamento real do suporte.

## Stack

- Laravel 13 (PHP 8.4+), Blade
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

## API externa: feriados nacionais (BrasilAPI)

O registro **manual** de chamados só é permitido em dias úteis. O Atendly consulta os feriados nacionais na [BrasilAPI](https://brasilapi.com.br/docs#tag/Feriados-Nacionais):

```http
GET https://brasilapi.com.br/api/feriados/v1/2026
→ [{"date": "2026-11-02", "name": "Finados", "type": "national"}, ...]
```

- `app/Services/BusinessCalendar.php` junta a regra de fim de semana com os feriados da API.
- O resultado fica em cache por 30 dias, então a API é chamada uma vez por ano consultado.
- Se a API estiver fora do ar, vale só a regra de fim de semana e uma nova tentativa é feita em 10 minutos. O sistema nunca para por causa dela.
- Em sábado, domingo ou feriado, o botão "Registrar chamado" fica desabilitado com o motivo ("Feriado: Finados") e a data do próximo dia útil. O envio também é recusado no servidor.
- Chamados vindos da **integração continuam sendo aceitos** nesses dias: eles são abertos no sistema de origem, e recusá-los faria o chamado se perder.
- A regra pode ser desligada com `ATENDLY_BLOCK_NON_BUSINESS_DAYS=false`.
- A conexão HTTPS usa os certificados do sistema ou o pacote da Mozilla (`composer/ca-bundle`), sem desligar a verificação SSL.

## Acessibilidade

Meta: **WCAG 2.1 nível AA**.

| Medida | Onde |
|---|---|
| Contraste mínimo de 4,5:1 em textos, selos e botões, nos temas claro e escuro (paleta recalculada) | `resources/scss/base/_theme.scss` |
| Link "Pular para o conteúdo" no primeiro Tab | layouts `app` e `auth` |
| `lang="pt-BR"`, landmarks (`main`, `nav`, `aside`) e título único por página | layouts |
| Página atual indicada com `aria-current` no menu e nos filtros | menu, fila, indicadores |
| Botões só com ícone têm nome acessível; o botão de som informa o estado (`aria-pressed`) | topo |
| Menu do celular com `aria-expanded`, foco no primeiro item e fechamento com Esc | `resources/js/modules/ui.js` |
| Mensagens de erro ligadas ao campo (`aria-invalid` + `aria-describedby`), com foco no primeiro campo inválido | formulários |
| Diálogo nativo (`<dialog>`) com foco preso, Esc para fechar e título associado | "Resolver chamado" |
| Avisos de novos chamados em região `aria-live` | fila |
| Gráficos com descrição em texto para leitores de tela | indicadores |
| Prioridade indicada por barras e texto, não só por cor; links em texto corrido sublinhados | componentes |
| Foco visível em todos os elementos; temas das entrevistas acessíveis por teclado | estilos |
| Respeita `prefers-reduced-motion` e `prefers-color-scheme` | estilos |

**Auditoria:** [axe-core 4.10](https://github.com/dequelabs/axe-core) (o motor do Lighthouse), regras WCAG 2 A/AA, 2.1 AA e boas práticas. **0 violações** em login, fila, chamado aberto, chamado encerrado, registro de chamado, busca, base de conhecimento, artigo, indicadores, entrevistas e formulário de entrevista, nos temas claro e escuro. Garantias estruturais (skip link, idioma, `aria-current`, nomes de botões) também têm testes automatizados em `tests/Feature/AccessibilityTest.php`.

## Publicação na nuvem (alwaysdata)

O Atendly roda no plano gratuito do [alwaysdata](https://www.alwaysdata.com): 1 GB de disco, 256 MB de RAM, PHP 8.4+, MariaDB e SSH. No passo a passo, troque `[conta]` pelo nome da sua conta.

### 1. No painel (uma vez só)

| Onde | O que fazer |
|---|---|
| **Environment > PHP** | Escolha **PHP 8.4** ou superior (o projeto exige ≥ 8.4.1) |
| **Databases > MySQL** | Crie o banco **`[conta]_atendly`** e anote usuário e senha (o host é `mysql-[conta].alwaysdata.net`) |
| **Remote access > SSH** | Habilite o usuário SSH com senha (ou cadastre sua chave pública) |
| **Web > Sites** | Edite o site `[conta].alwaysdata.net`: tipo **PHP**, *Root directory* **`/atendly/public`** |

### 2. Primeira instalação (do seu computador)

```powershell
git push
.\deploy\deploy.ps1 -Conta [conta] -Install
```

Na primeira execução, o script baixa o código no servidor, cria o `~/atendly/.env` a partir de [`deploy/env.alwaysdata.example`](deploy/env.alwaysdata.example) e para, pedindo os dados do banco. Preencha:

```bash
ssh [conta]@ssh-[conta].alwaysdata.net
nano ~/atendly/.env     # APP_URL, DB_DATABASE, DB_USERNAME, DB_PASSWORD
```

Rode `.\deploy\deploy.ps1 -Conta [conta] -Install` de novo. Ele gera `APP_KEY`, o token da integração e uma **senha forte para as contas demo**, mostrada ao final, cria as tabelas e carrega a base demo.

### 3. Atualizações

```powershell
git push
.\deploy\deploy.ps1 -Conta [conta]
```

O CSS/JS é compilado no seu computador e enviado por `scp`, porque o plano gratuito tem pouca RAM para o Vite. O resto (`git pull`, `composer install --no-dev`, migrations e cache) roda no servidor via [`deploy/server.sh`](deploy/server.sh).

**Detalhes de produção já configurados:** `APP_DEBUG=false`, cookies de sessão só em HTTPS, confiança no proxy HTTPS do alwaysdata (links gerados com `https://`), cache de configuração, rotas e views (`php artisan optimize`), e senha das contas demo definida por `ATENDLY_DEMO_PASSWORD`, nunca "password" em produção. Para o "esqueci a senha" enviar e-mails de verdade, crie um endereço em **Emails** no painel e troque `MAIL_MAILER=log` por `smtp` no `.env`.

## Configuração (`.env`)

| Variável | Padrão | Uso |
|---|---|---|
| `ATENDLY_INTEGRATION_TOKEN` | vazio (webhook desativado) | Token exigido pelo webhook |
| `ATENDLY_POLL_SECONDS` | 15 | Intervalo de verificação da fila |
| `ATENDLY_ACK_ALERT_MINUTES` | 15 | Meta de percepção para chamados sem categoria |
| `APP_TIMEZONE` | America/Sao_Paulo | Fuso usado nos horários |
| `ATENDLY_BLOCK_NON_BUSINESS_DAYS` | true | Bloqueia o registro manual em fins de semana e feriados |
| `ATENDLY_HOLIDAYS_API` | BrasilAPI | Endereço da API de feriados |
| `ATENDLY_SHOW_TEST_MARKS` | false | Mostra o selo "Teste" nos dados demo |
| `ATENDLY_DEMO_PASSWORD` | password | Senha das contas criadas pelo seeder (troque em produção) |

As metas de resposta e de resolução por categoria ficam na tabela `categories`.

## Estrutura

```
app/
  Enums/                 TicketStatus, TicketPriority, UserRole
  Services/
    TicketService.php    ciclo de vida: receber, assumir, interagir, encerrar, vincular artigo
    KnowledgeSearch.php  busca FULLTEXT (MySQL) com fallback LIKE (SQLite nos testes)
    MetricsService.php   indicadores do diagnóstico
    BusinessCalendar.php dias úteis: fim de semana + feriados da BrasilAPI
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

Os testes usam SQLite em memória (extensão `pdo_sqlite` necessária), com o relógio fixo numa quarta-feira e a BrasilAPI simulada (`Http::fake`). Eles não dependem do dia nem da internet. Nesse caso a busca usa o fallback com `LIKE`, então o ranqueamento FULLTEXT só é exercitado com MySQL.

## Hipóteses embutidas na base sintética

O seeder gera atrasos de percepção maiores no almoço, fora do expediente e no fim de semana, e repete alguns problemas com mais frequência (senha, impressora, VPN, ERP). Essas são **hipóteses a investigar** nas entrevistas, não constatações. Ajuste `database/seeders/data/scenarios.php` e `SyntheticDataSeeder::perceptionDelay()` conforme o diagnóstico avançar.
