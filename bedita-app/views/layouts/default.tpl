{agent var="agent"}
<!DOCTYPE html>
<html lang="it">
<head>
	<title>BEdita | {$currentModule.label|default:'home'} | {$html->action} | {if !empty($object)}{$object.title|default:"<i>[no title]</i>"}{/if}</title>

	{if $agent.iPHONE or $agent.iPAD}
		
		<meta name="viewport" content="user-scalable=yes, width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
	    <meta name="apple-mobile-web-app-capable" content="yes" />
	    <meta name="apple-mobile-web-app-status-bar-style" content="black" />
	    <link rel="apple-touch-icon" href="{$html->url('/img/')}BElogo_iphone.png"/>
	    <link rel="apple-touch-startup-image" href="{$html->url('/img/')}BElogo_iphone.png" />
		<link rel="stylesheet" href="{$html->url('/css/')}beditaMobile.css" type="text/css" media="screen" title="main" charset="utf-8">
		
	{/if}
		
	{include file="inc/meta.tpl"}
	
	{$html->css('beditaNew')}
	
	<!--[if lte IE 6]>
	
		{$html->css('IE6fix')}
		
	<![endif]-->

	{$html->script("jquery/jquery")}
	{$html->script("jquery/jquery.cookie")}
	{$html->script("jquery/jquery.autogrow")}
	{$html->script("jquery/jquery.dimensions")}
	{$html->script("beditaUI")}

	{$beurl->addModuleScripts()}

	{* collect linked scripts around *}
	{$scripts_for_layout}

	{$html->script("jquery/ui/jquery-ui-1.8rc3.custom")}
	{$html->script("jquery/ui/jquery.ui.draggable")}

	
</head>
<body{if !empty($bodyClass)} class="{$bodyClass}"{/if}>


{$content_for_layout}
	
{*
** Help container
*}

{$view->element('help')}


{*
** Modal container
*}

{$view->element('modal')}


{*
** Page Footer
*}

{if empty($noFooter)}

{$view->element('footer')}

{/if}


{* HTML document's end *}
</body>
</html>