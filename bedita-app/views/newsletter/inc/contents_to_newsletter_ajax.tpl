{strip}
{if $contentTemplate}
	{foreach from=$objects item="obj"}
		<div style="clear: both">
		{if !empty($obj.relations.attach)}
			{assign_associative var="params" width=96 height=96 mode="fill" upscale=false URLonly=1}
			{assign_concat var="src" 0='src="' 1=$beEmbedMedia->object($obj.relations.attach.0,$params) 2='"'}
			{assign var="content" value=$contentTemplate|regex_replace:'/src="[\S]*?"/':$src}
		{else}
			{assign var="content" value=$contentTemplate|regex_replace:"/\<img.[\S\s]*?\>/":""}
		{/if}
		
		{assign var="content" value=$content|replace:"[\$title]":$obj.title}
		{assign var="content" value=$content|replace:"[\$description]":$obj.description}
		
		{assign var="bodyTruncated" value=$obj.body|html_substr:$truncateNumber:"..."}
		{assign_concat var="regexp" 0="/\[" 1="\\$" 2="body.*\]/"}
		
		{assign var="content" value=$content|regex_replace:$regexp:$bodyTruncated}
		
		
		{$content}
		</div>
	{/foreach}
{else}
	<div style="clear: both">
	{if !empty($obj.relations.attach)}
		{assign_associative var="params" width=96 height=96 mode="fill" upscale=false}
		{assign_associative var="htmlAttr" width=96 height=96}
		<div style="float:left;margin:0px 20px 20px 0px;">
		{$beEmbedMedia->object($obj.relations.attach.0,$params,$htmlAttr)}
		</div>
	{/if}
	<h2>{$obj.title}</h2>
	{if !empty($obj.description)}
		<h3>{$obj.description}</h3>
	{/if}
	{if !empty($obj.body)}{$obj.body|html_substr:128:"..."}{/if}
	</div>
{/if}
{/strip}
