{extends file='parent:frontend/index/header.tpl'}

{block name="frontend_index_header_css_screen"}
    {if $controllerStylesheet}
        <link rel="preload" as="style" href="{$controllerStylesheet}">
        <link href="{$controllerStylesheet}" media="all" rel="stylesheet" type="text/css">
    {/if}
    {$smarty.block.parent}
{/block}