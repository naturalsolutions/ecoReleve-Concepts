<?php
    header("Content-type: text/css");
?>

/* begin Box, Sheet */

.td, .td-body
{
	zoom:1;
}

.td-body
{
	position:static;
}

.td-tr, .td-tl, .td-br, .td-bl, .td-tc, .td-bc,.td-cr, .td-cl
{
	font-size: 1px;
	background: none;
}

.td-tr, .td-tl, .td-br, .td-bl
{ 
	filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='Sheet-s.gif', sizingMethod='scale');background-image: none;zoom: 1;
}

.td-tl
{
	clip: rect(auto 24px 24px auto);
}

.td-tr
{
	left: expression(this.parentNode.offsetWidth-48+'px');
	clip: rect(auto auto 24px 24px);
}

.td-bl
{
	top: expression(this.parentNode.offsetHeight-48+'px');
	clip: rect(24px 24px auto auto);
}

.td-br
{
	top: expression(this.parentNode.offsetHeight-48+'px');
	left: expression(this.parentNode.offsetWidth-48+'px');
	clip: rect(24px auto auto 24px);
}

.td-tc, .td-bc
{
	width: expression(this.parentNode.offsetWidth-48+'px');
	filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='Sheet-h.gif', sizingMethod='scale');background-image: none;zoom: 1;
}

.td-tc
{
	clip: rect(auto auto 24px auto);
}

.td-bc
{
	top: expression(this.parentNode.offsetHeight-48+'px');
	clip: rect(24px auto auto auto);
}

.td-cr, .td-cl
{
	height: expression(this.parentNode.offsetHeight-48+'px');
	filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='Sheet-v.gif', sizingMethod='scale');background-image: none;zoom: 1;
}

.td-cr
{
	left: expression(this.parentNode.offsetWidth-48+'px');
	clip: rect(auto auto auto 24px);
}

.td-cl
{
	clip: rect(auto 24px auto auto);
}

.td-cc
{
	font-size: 1px;
	width: expression(this.parentNode.offsetWidth-48+'px');
	height: expression(this.parentNode.offsetHeight-48+'px');
	background: none;
	filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='Sheet-c.gif', sizingMethod='scale');background-image: none;zoom: 1;
}


