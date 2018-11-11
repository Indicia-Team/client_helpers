<?php

global $indicia_templates;

$indicia_templates['formControlClass'] = 'form-control';
$indicia_templates['controlWrap'] =
  '<div id="ctrl-wrap-{id}" class="form-group ctrl-wrap">{control}</div>' . "\n";
$indicia_templates['controlWrapErrorClass'] = 'has-error';
$indicia_templates['controlAddonsWrap'] =
  '<div class="input-group">{control}<div class="input-group-addon ctrl-addons">{addons}</div></div>';
$indicia_templates['two-col-50'] =
  '<div class="row"><div class="col-md-6">{col-1}</div><div class="col-md-6">{col-2}</div></div>';

// Remove cols from textarea
$indicia_templates['textarea'] =
  '<textarea id="{id}" name="{fieldname}"{class} {disabled} rows="{rows}" {title}>{default}</textarea>'."\n";

// Switch to Bootstrap button classes.
$indicia_templates['buttonDefaultClass'] = 'indicia-button btn btn-default';
$indicia_templates['buttonHighlightedClass'] = 'indicia-button btn btn-primary';
$indicia_templates['buttonWarningClass'] = 'indicia-button btn btn-danger';
$indicia_templates['anchorButtonClass'] = 'indicia-button btn btn-default';

// Message boxes
$indicia_templates['messageBox'] = '<div class="alert alert-info">{message}</div>';