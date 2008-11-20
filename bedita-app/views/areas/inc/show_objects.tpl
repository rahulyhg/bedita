<script type="text/javascript">
<!--

var urlShowObj = "{$html->here}";
if (!urlAddObjToAss) 
	var urlAddObjToAss = "{$html->url('/areas/loadObjectToAssoc')}";
var relType = "{$relation|default:""}";

{literal}
function loadObjToAssoc(page) {
	$("#loadObjInModal").show();
	$("#assocObjContainer").empty().load(urlShowObj, 
			{
				"parent_id": $("#parent_id").val(),
				"objectType": $("#objectType").val(),
				"lang": $("#lang").val(),
				"search": $("#search").val(),
				"page": page
			},
			function() {
				$("#loadObjInModal").hide();
	});
}

$(document).ready(function() {

	$("#searchButton").click(function() {
		loadObjToAssoc(1);
	});
	
	$("#addButton").click(function() {
		obj_sel = {relation: relType};
		obj_sel.object_selected = "";
		
		$("#assocObjContainer :checked").each(function() {
			obj_sel.object_selected += $(this).val() + ","; 
		});
		
		if (obj_sel.object_selected != "") {
			
			$("#modal").hide();
			$("#modaloverlay").hide();
			
			// addObjToAssoc function has to be defined in other template (i.e. common_inc/form_assoc_objects.tpl)
			addObjToAssoc(urlAddObjToAss, obj_sel);
			
		}
	});

});
{/literal}
//-->
</script>

<div class="body bodybg">


	<label>Cerca:</label> &nbsp; <input type="text" name="search" id="search" value="">
	&nbsp;&nbsp;
	in: <select style="width:180px" name="parent_id" id="parent_id">
			{$beTree->option($tree)}
		</select>
	<hr>
	
	
	tipo:
	<select name="objectType" id="objectType">
		<option value="">{t}all{/t}</option>
		{foreach from=$objectTypeIds item=type_id}
			{if $type_id}
			{strip}
			<option value="{$type_id}">
				{$conf->objectTypes[$type_id].name|lower}
			</option>
			{/strip}
			{/if}
		{/foreach}
		</select>
	
	&nbsp;&nbsp;
	lingua: 
	<select name="lang" id="lang">
		<option value="">{t}all{/t}</option>
		{foreach key=val item=label from=$conf->langOptions}
			{strip}
			<option value="{$val}">
				{$label}
			</option>
			{/strip}
		{/foreach}
	</select>
	
	&nbsp;&nbsp;
	<input type="button" id="searchButton" value=" {t}Search{/t} ">
	<hr />
	
	<div id="loadObjInModal" class="loader"><span></span></div>
	
	<div id="assocObjContainer">
		{include file="inc/list_contents_to_assoc.tpl"}
	</div>

	<div class="modalcommands">
		
		<input type="button" id="addButton" style="width:300px" value=" {t}add{/t} ">
	
	</div>
</div>



	