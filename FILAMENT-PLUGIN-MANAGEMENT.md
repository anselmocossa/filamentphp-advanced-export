# Gestao do Plugin no filamentphp.com

## Portal de Autor

O site filamentphp.com ja **nao aceita PRs** para gerir plugins. O repo `filamentphp/filamentphp.com` foi arquivado e renomeado para `filamentphp/legacy-site`.

Toda a gestao de plugins e feita pelo portal de autor:

**URL:** https://filamentphp.com/author

- Login via OAuth GitHub (autorizar app "Filament")
- Pagina de plugins: https://filamentphp.com/author/anselmo-kossa/plugins
- Editar plugin: https://filamentphp.com/author/anselmo-kossa/plugins/anselmo-kossa-advanced-export/edit

## Como actualizar versoes suportadas

1. Ir a https://filamentphp.com/author
2. Autorizar com GitHub (se necessario)
3. Clicar no plugin "Advanced Export"
4. Na seccao **"Compatibility & features"** > **"Versions"**, adicionar/remover versoes (v1-v5)
5. Clicar **"Submit for approval"** (ou "Save draft" se o botao estiver desactivado)

## Historico de versoes

| Data       | Versoes suportadas | Notas                          |
|------------|-------------------|--------------------------------|
| 2026-01-25 | v4                | Publicacao inicial             |
| 2026-03-31 | v4, v5            | Adicionado suporte Filament v5 |

## Dados do Plugin

- **Slug:** anselmo-kossa-advanced-export
- **Repo GitHub:** anselmocossa/filamentphp-advanced-export
- **Pagina publica:** https://filamentphp.com/plugins/anselmo-kossa-advanced-export
- **Docs URL:** https://raw.githubusercontent.com/anselmocossa/filamentphp-advanced-export/main/README.md
- **Categorias:** Tables, Panels
- **Status:** Approved

## Notas

- O repo `anselmocossa/filamentphp.com` (fork antigo) esta desactualizado e aponta para o repo arquivado. Nao usar para PRs.
- O `composer.json` do pacote tambem deve ser actualizado para reflectir compatibilidade (ex: `"filament/filament": "^4.0|^5.0"`).
