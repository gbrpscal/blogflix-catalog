# Padrão de desenvolvimento

Este documento é o acordo técnico do Blogflix. Alterações devem preservar autenticação por sessão, contratos REST versionados, isolamento dos usuários e a fronteira backend com serviços externos.

## Princípios

1. Segredos pertencem ao `.env`; nenhum `VITE_*` pode conter credenciais.
2. O navegador conversa somente com o Nginx/Laravel.
3. Controllers coordenam; regras ficam em Actions ou Services.
4. Toda entrada HTTP passa por Form Request.
5. Toda saída pública estruturada passa por API Resource.
6. Toda autorização existe no backend, independentemente do Router Vue.
7. Migrations e restrições do banco protegem invariantes concorrentes.
8. Integrações externas precisam de timeout, erro controlado, fake e cache quando aplicável.
9. Uma falha externa nunca deve revelar token, stack trace ou detalhes internos ao cliente.
10. Uma mudança só está pronta depois dos testes, linters e build relevantes.

## Fluxo de uma funcionalidade

Backend:

```text
Route -> Middleware -> FormRequest -> Controller -> Action/Service
                                              -> Model/Policy/PostgreSQL
                                              -> Client/DTO/Cache
                     <- API Resource/JSON <-
```

Frontend:

```text
View -> component/store -> módulo API -> Axios -> /api/v1
     <- estado tipado   <- resposta normalizada <-
```

Antes de mudar um endpoint, defina request, status codes, resposta, autorização, efeitos de cache e testes.

## Convenções backend

- PHP formatado pelo Pint, strict typing onde trouxer valor sem quebrar o padrão Laravel.
- Namespaces seguem PSR-4 e classes têm uma responsabilidade.
- Controllers ficam em `App\Http\Controllers\Api\V1`.
- Requests e Resources espelham a versão da API.
- Actions representam comandos de domínio, por exemplo `AddFavorite`.
- Services coordenam casos de uso externos, por exemplo `TmdbMovieService`.
- Clients encapsulam protocolo e credencial, por exemplo `TmdbClient`.
- DTOs normalizam o payload antes que ele alcance controller/model.
- Exceptions de domínio têm tradução JSON central.
- Policies usam negação 404 quando a existência de um recurso alheio não deve ser exposta.
- Queries por usuário devem começar pela relação do usuário autenticado.
- Não altere migration já compartilhada; crie uma nova migration reversível.

Status HTTP adotados:

- 200 leitura/operação bem-sucedida;
- 201 criação;
- 204 remoção/logout sem corpo;
- 401 sessão ausente;
- 404 recurso inexistente ou pertencente a outro usuário;
- 409 conflito de unicidade;
- 422 validação;
- 429 rate limit;
- 502/503 falha/indisponibilidade de serviço externo.

## Convenções frontend

- Vue 3 com `<script setup lang="ts">` e Composition API.
- Props, emits, respostas e estado devem ser tipados.
- Views coordenam páginas; componentes reutilizáveis não fazem navegação implícita.
- Chamadas HTTP ficam em `src/api`; não use Axios diretamente em componentes.
- Estado global somente quando compartilhado. Estado de formulário permanece local.
- Nunca persistir sessão ou dados sensíveis em localStorage/sessionStorage.
- Cada operação assíncrona trata loading, vazio e erro.
- Controles têm label/nome acessível, foco visível e funcionamento por teclado.
- CSS parte de telas pequenas e evita largura fixa que quebre responsividade.
- Texto de erro ao usuário não exibe stack trace nem resposta bruta do servidor.

## Autenticação

Sempre obtenha o cookie CSRF antes de operações mutáveis de autenticação. Axios deve continuar com `withCredentials: true` e `withXSRFToken: true`.

O store guarda o usuário somente em memória e restaura a sessão consultando `/api/v1/auth/user`. Guards Vue são UX; os middlewares `auth:sanctum` e `verified` são a autoridade.

Ao adicionar um provedor OAuth:

- solicite apenas scopes necessários;
- valide e-mail verificado;
- não armazene tokens sem caso de uso;
- cubra criação, vínculo, falha e configuração ausente.

## Banco e PostgreSQL

- Nomes em snake_case, FK explícita, índices guiados por query.
- Invariantes críticas têm constraint, não apenas validação PHP.
- JSONB é reservado a arrays/objetos consultados como documento.
- Para gêneros, persista lista de inteiros, use `whereJsonContains` e mantenha GIN.
- Testes feature usam exclusivamente `blogflix_testing`.
- Nunca aponte `POSTGRES_TEST_DB` para o banco local/produção.

## Redis e filas

Conexões lógicas:

- DB 0: conexão Redis default/rate limit;
- DB 1: cache;
- DB 2: sessões;
- DB 3: filas.

Toda mudança de cache deve documentar chave, variações e TTL. Nunca inclua token na chave.

Notificações de conta são criptografadas e usam a fila `emails`. Jobs devem ser idempotentes quando possível, ter tentativas limitadas e backoff. Alterar `APP_KEY` invalida jobs criptografados ainda pendentes.

## Testes

Pirâmide mínima para cada regra:

- Unit: normalização/transformação pura e decisões sem I/O.
- Feature: rota, validação, autenticação, autorização, persistência e status.
- Frontend: comportamento visível de componentes e stores.

Regras externas:

- TMDB: `Http::fake()` + `Http::preventStrayRequests()`;
- e-mail/notificações: fakes;
- Socialite: provider/user fake;
- banco: factories + `RefreshDatabase`;
- nenhum teste depende de rede, Google, TMDB, SMTP ou banco de desenvolvimento.

Ao corrigir uma falha, preserve o teste que a detectou e adicione uma regressão se o cenário ainda não estiver explícito.

## Comandos de qualidade

```bash
docker compose config --quiet
docker compose --profile tools run --rm backend-test composer test
docker compose --profile tools run --rm backend-test composer lint
docker compose --profile tools run --rm frontend-tooling npm test
docker compose --profile tools run --rm frontend-tooling npm run type-check
docker compose --profile tools run --rm frontend-tooling npm run lint
docker compose --profile tools run --rm frontend-tooling npm run format:check
docker compose build backend worker nginx
docker build --file docker/railway/web.Dockerfile --tag blogflix-web:local .
python3 -m json.tool railway/web.json
python3 -m json.tool railway/backend.json
python3 -m json.tool railway/worker.json
```

Para mudanças de infraestrutura, também execute `docker compose up -d`, confira `docker compose ps`, migrations, health endpoint e logs de init/worker.

Os serviços Railway usam configuração como código separada em `railway/`. Backend e worker compartilham a imagem PHP, mas têm processos e ciclos de deploy independentes. Somente o web recebe domínio público; migrations pertencem ao pre-deploy do backend.

## Git e revisão

- Não faça commit sem autorização do responsável.
- Uma mudança lógica por commit.
- Prefixos: `chore`, `feat`, `fix`, `test`, `docs`, `refactor`.
- Use uma branch de trabalho e integre em `main` somente depois que o CI estiver verde.
- Não misture formatação global com mudança funcional.
- Antes de entregar, liste arquivos, resumo, comandos, testes e sugestão de commit.
- Revise `git diff --check`, `git status --short` e procure segredos.

Checklist de review:

- o endpoint exige os middlewares corretos?
- existe Form Request e Policy quando aplicável?
- a query está limitada ao usuário?
- concorrência está protegida pelo banco?
- erros externos são controlados?
- cache varia por todos os parâmetros relevantes?
- a credencial pode aparecer em log/resposta/bundle?
- há estados loading/vazio/erro e acessibilidade?
- há testes independentes de rede?

## Definition of Done

Uma etapa está concluída quando:

- contrato e implementação estão coerentes;
- migrations sobem em banco vazio;
- testes novos e existentes passam;
- Pint, ESLint, Prettier e TypeScript passam;
- build de produção termina;
- healthchecks ficam saudáveis;
- nenhum segredo foi adicionado;
- README/`.env.example` foram atualizados quando a operação mudou;
- limitações e intervenção humana foram explicitadas.
