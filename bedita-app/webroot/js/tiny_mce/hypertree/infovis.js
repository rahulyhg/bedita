var Infovis={Accordion:{colorBackgroundSelected:"#78BA91",colorFontSelected:"#fff",colorBackground:"#7389AE",colorFont:"#fff",effects:{}},initLayout:function(){var J=Window.getSize();var E=$("header"),D=$("left"),H=$("infovis");var I=E.getSize().y,B=D.getSize().x;var A={height:Math.floor((J.y-I)/1),width:Math.floor((J.x-B)/1)};H.setProperties(A);H.setStyles(A);H.setStyles({position:"absolute",top:I+"px",left:B+"px"});D.setStyle("height",A.height);var C=new Array();var F=0;var G=this;$$("#left .left-item").each(function(K){G.Accordion.effects[K.innerHTML]=new Fx.Morph(K,{duration:300,Transition:Fx.Transitions.Quart.easeOut});F+=K.offsetHeight});$$("#left .small-title").each(function(K){F+=K.offsetHeight})},initAccordion:function(B){var C=this;var A=new Accordion("div.left-item","div.contained-item",{fixedHeight:B,onActive:function(F){var E=C.Accordion.effects[F.innerHTML];var D=C;E.start({color:D.Accordion.colorFontSelected,"background-color":D.Accordion.colorBackgroundSelected})},onBackground:function(F){var E=C.Accordion.effects[F.innerHTML];var D=C;E.start({color:D.Accordion.colorFont,"background-color":D.Accordion.colorBackground})}},$("left"))}};