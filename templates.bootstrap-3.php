<?php

global $indicia_templates;

$indicia_templates['formControlClass'] = 'form-control';
$indicia_templates['controlWrap'] =
  '<div id="ctrl-wrap-{id}" class="form-group ctrl-wrap{wrapClasses}">{control}</div>' . "\n";
$indicia_templates['controlWrapErrorClass'] = 'has-error';
$indicia_templates['controlAddonsWrap'] =
  '<div class="input-group">{control}<div class="input-group-addon ctrl-addons">{addons}</div></div>';
$indicia_templates['two-col-50'] =
  '<div class="row"{attrs}><div class="col-md-6">{col-1}</div><div class="col-md-6">{col-2}</div></div>';

// Remove cols from textarea
$indicia_templates['textarea'] =
  '<textarea id="{id}" name="{fieldname}"{class} {disabled} rows="{rows}" {title}>{default}</textarea>'."\n";

// Switch to Bootstrap button classes.
$indicia_templates['buttonDefaultClass'] = 'indicia-button btn btn-default';
$indicia_templates['buttonHighlightedClass'] = 'indicia-button btn btn-primary';
$indicia_templates['buttonWarningClass'] = 'indicia-button btn btn-danger';
$indicia_templates['anchorButtonClass'] = 'indicia-button btn btn-default';

$indicia_templates['messageBox'] = '<div class="alert alert-info">{message}</div>';
$indicia_templates['warningBox'] = '<div class="alert alert-warning"><span class="fas fa-exclamation-triangle"></span>{message}</div>';

$indicia_templates['speciesDetailsThumbnail'] = <<<HTML
<div class="thumbnail">
  <a data-fancybox="gallery" href="{imageFolder}{the_text}">
    <img src="{imageFolder}{imageSize}-{the_text}" title="{caption}" alt="{caption}"/><br/>
    {caption}
  </a>
</div>

HTML;

$indicia_templates['autocomplete_new_taxon_form'] = <<<HTML
<div style="display: none">
  <fieldset class="popup-form" id="new-taxon-form" disabled >
    <legend>{title}</legend>
    <p>{helpText}</p>
    <div class="form-group">
      <label for="new-taxon-name">Taxon name:</label>
      <div class="input-group">
        <input type="text" id="new-taxon-name" class="form-control {required:true}"/>
        <div class="input-group-addon ctrl-addons">
          <span class="deh-required">*</span>
        </div>
      </div>
    </div>
    <div class="form-group">
      <label for="new-taxon-group">Taxon group:</label>
      <div class="input-group">
        <select id="new-taxon-group" class="form-control {required:true}">
          {taxonGroupOpts}
        </select>
        <div class="input-group-addon ctrl-addons">
          <span class="deh-required">*</span>
        </div>
      </div>
    </div>
    <button type="button" class="btn btn-primary" id="do-add-new-taxon">Add taxon</button>
  </fieldset>
</div>

HTML;
