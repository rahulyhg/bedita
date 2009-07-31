{strip}

<div class="tab"><h2>{t}BEdita contents statistics{/t}</h2></div>
	<div id="objects">
		
		<h2>{t}Number of objects{/t}</h2>
		
		<table class="graph">
			{foreach from=$totalObjectsNumber key="objectType" item="num" name="fctotal"}
				{if $num > 0} 
				{if isset($conf->objectTypes[$objectType].module)}
				<tr>
					<td class="label">{$objectType}</td>
					<td>
						{math assign="pixel" equation="(x/y)*400" x=$num y=$maxTotalObjectsNumber}
						<div style="width:{$pixel}px;" class="{$conf->objectTypes[$objectType].module}">&nbsp;</div> <span class="value">{$num}</span>
					</td>
				</tr>
				{/if}
				{/if}
			{/foreach}
		</table>
		
		<hr />
		
		<h2>{t}Evolution of content production, during the time{/t}</h2>
		
		<table class="graph">
			{foreach from=$timeEvolution key="date" item="types" name=""}
			<tr>
				<td class="label">{$date|date_format:"%b %Y"}</td>
				<td>
				{foreach from=$types key="objectType" item="num"}
					{if isset($conf->objectTypes[$objectType].module)}
					{math assign="pixel" equation="(x/y)*400" x=$num y=$maxTotalTimeEvolution}
					<div style="width:{$pixel}px;" class="{$conf->objectTypes[$objectType].module}">&nbsp</div>
					{/if}
				{/foreach}
					<span class="value">{$totalTimeEvolution[$date]}</span>
				</td>
			</tr>
			{/foreach}
		</table>
		
		<hr />	

		<h2>{t}Contents with more comments (first 20){/t}</h2>
		
		<table class="graph">
			{foreach from=$contentCommented item="c"}
			{math assign="pixel" equation="(x/y)*350" x=$c.count_relations y=$maxContentCommented}
			{if isset($c.ObjectType.module)}
			<tr>
				<td class="label">{$c.title|truncate:20|default:'<i>[no title]</i>'}</td>
				<td>
					<div style="width:{$pixel}px;" class="{$c.ObjectType.module}">&nbsp</div><span class="value">{$c.count_relations}</span>
				</td>
			</tr>
			{/if}
			{/foreach}
		</table>
	
		<hr />
		
		<h2>{t}Objects with multiple relationships{/t}</h2>

		<table class="graph">
			{foreach from=$relatedObject item="c"}
			{math assign="pixel" equation="(x/y)*350" x=$c.count_relations y=$maxRelatedObject}
			{if isset($c.ObjectType.module)}
			<tr>
				<td class="label">
					<a href="{$html->url('/')}{$c.ObjectType.module}/view/{$c.id}">
					{$c.title|truncate:20|default:'<i>[no title]</i>'}</a>
				</td>
				<td>
					<div style="width:{$pixel}px;" class="{$c.ObjectType.module}">&nbsp</div><span class="value">{$c.count_relations}</span>
				</td>
			</tr>
			{/if}
			{/foreach}
		</table>

</div>

{/strip}
