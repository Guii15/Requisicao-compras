# Módulo de Conferência — Sub-projeto 4: Ajustes na Tela do Conferente (câmera + abas)

> Referência geral do módulo completo: `PLANO-CONFERENCIA.md` (fornecido pelo Guilherme).
> Referência do sub-projeto anterior: `docs/superpowers/specs/2026-08-03-tela-do-conferente-design.md`
> (criou a Tela do Conferente: listagem + modal de conferência por item).

## 1. Objetivo desta fatia

Dois ajustes pequenos na Tela do Conferente feitos depois de testar a v1 na prática:
1. O campo de foto precisa permitir tirar foto na hora (câmera), não só escolher arquivo
   já existente.
2. A listagem precisa separar visualmente o que ainda está aguardando conferência do que
   já foi conferido — hoje só mostra os pendentes, os já conferidos somem sem deixar rastro
   visível na tela.

## 2. Contexto relevante do sistema atual

- `resources/views/conferencia/index.blade.php` (sub-projeto 3) lista só
  `status='aprovado'` + `status_conferencia` nulo, com input `<input type="file" name="foto"
  accept=".jpg,.jpeg,.png,.webp" required>` no modal — sem atributo de câmera.
- `ConferenciaController::index()` hoje não aceita parâmetro de aba/filtro.
- Padrão de abas já existe em `resources/views/admin/index.blade.php` (linhas ~61-74):
  links de página inteira (não JS), estilo com `background`/`border` diferente pra aba ativa.
- Valores de `status_conferencia` já definidos (sub-projeto 1): `conferido_ok`,
  `divergente`, `avancado_mesmo_assim`.

## 3. Decisões tomadas no brainstorming

1. **Foto**: permitir tanto câmera quanto galeria (não travar só na câmera) — atributo
   `capture="environment"` somado ao `accept` já existente. Em desktop sem câmera, o atributo
   é ignorado e o seletor de arquivo normal continua funcionando.
2. **Separação**: abas "Aguardando" / "Conferidos", mesmo padrão visual/técnico das abas do
   Admin (link de página inteira, não JS/Alpine).
3. **Conteúdo da aba "Conferidos"**: mostra tudo que já tem `status_conferencia` preenchido
   (os três valores — OK, Divergente, Avançado Mesmo Assim), com uma etiqueta colorida
   indicando o resultado. Sem ação nessa aba (só consulta).
4. **Fora de escopo**: ver a foto tirada, editar uma conferência já feita, e a divisão de
   quantidade entre estoque/entrega direta (isso é uma fatia separada, maior, que vem depois
   desta).

## 4. Mudanças

### 4.1 `resources/views/conferencia/index.blade.php` — campo de foto
No `<input type="file" name="foto">` do modal, adicionar `capture="environment"`:
```html
<input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" capture="environment" required ...>
```

### 4.2 `app/Http/Controllers/ConferenciaController.php` — `index()`
Passa a ler `$aba = $request->query('aba', 'aguardando')` e monta a query condicionalmente:
```php
public function index(Request $request)
{
    $aba = $request->query('aba', 'aguardando') === 'conferidos' ? 'conferidos' : 'aguardando';

    $query = PurchaseRequest::with('user')->where('status', 'aprovado');

    if ($aba === 'conferidos') {
        $query->whereNotNull('status_conferencia');
    } else {
        $query->whereNull('status_conferencia');
    }

    $requests = $query->latest()->paginate(15)->withQueryString();

    return view('conferencia.index', compact('requests', 'aba'));
}
```
(`withQueryString()` preserva `?aba=conferidos` ao trocar de página na paginação.)

### 4.3 `resources/views/conferencia/index.blade.php` — abas e coluna final
Duas abas no topo (mesmo estilo do Admin), links para
`route('conferencia.index')` (Aguardando) e `route('conferencia.index', ['aba' => 'conferidos'])`
(Conferidos), destacando a ativa conforme `$aba`.

A última coluna da tabela muda conforme `$aba`:
- `aguardando`: cabeçalho "Ação", célula com o botão "Conferir" (like hoje).
- `conferidos`: cabeçalho "Resultado", célula com etiqueta:
  - `conferido_ok` → 🟢 fundo `#dcfce7` texto `#16a34a` "OK"
  - `divergente` → 🔴 fundo `#fee2e2` texto `#dc2626` "Divergente"
  - `avancado_mesmo_assim` → 🔵 fundo `#dbeafe` texto `#2563eb` "Avançado Mesmo Assim"

Mensagem de lista vazia também muda conforme a aba ("Nenhuma requisição aguardando
conferência" vs "Nenhuma requisição conferida ainda").

## 5. Fora de escopo

- Visualizar a foto tirada (ex: modal com a imagem).
- Editar/reverter uma conferência já registrada.
- Divisão de quantidade entre estoque e entrega direta (fatia separada e maior).
- Tela de Pendências, ajustes em Entrada/Vendedor, SLA.

## 6. Plano de teste (local apenas)

1. Feature tests em `ConferenciaControllerTest` (arquivo já existente):
   - `index()` sem parâmetro (ou `?aba=aguardando`) continua mostrando só os pendentes,
     como já testado no sub-projeto 3 — sem regressão.
   - `index(?aba=conferidos)` mostra requisições com `status_conferencia` em qualquer um
     dos 3 valores, e NÃO mostra as ainda pendentes.
   - A view renderiza a etiqueta correta pra cada um dos 3 valores de `status_conferencia`
     na aba Conferidos.
2. Confirmar visualmente em local que o campo de foto abre o seletor com opção de câmera
   (checagem do atributo HTML, já que emular câmera de verdade não é possível no ambiente
   de teste) e que as abas navegam corretamente preservando a aba selecionada na paginação.

Nenhum teste em produção ou em ambiente de staging nesta fatia.
