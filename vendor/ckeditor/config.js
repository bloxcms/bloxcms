/**
 * @license Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here.
	// For the complete reference:
	// http://docs.ckeditor.com/#!/api/CKEDITOR.config

	// The toolbar groups arrangement, optimized for two toolbar rows.
	config.toolbarGroups =
	[
		{ name: 'clipboard',   groups: [ 'clipboard', 'undo' ] }, // Copy, paste
		{ name: 'styles' },
		{ name: 'links' },
		{ name: 'insert' }, // Insert an image, table, ..
		{ name: 'document',	   groups: [ 'mode', 'document',] }, // Source
		{ name: 'tools' }, // Expand
		'/',
		{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
		{ name: 'paragraph',   groups: [ 'list', 'indent', 'align' ] },
		{ name: 'colors' },

	];
	// Remove some buttons, provided by the standard plugins, which we don't
	// need to have in the Standard(s) toolbar.
	//config.removeButtons = 'Underline,Subscript,Superscript';
	config.removeButtons = 'Save,NewPage,Preview,Print,PageBreak,Styles,Format,Smiley,ShowBlocks,Font,PasteFromWord,Iframe,Anchor,Flash,HorizontalRule';

	config.uiColor = '#d4d0c8';
	config.width = 580;
	//config.language = 'en';
    config.allowedContent = true;
    config.forceSimpleAmpersand = true; // Do not replase & to &amp; in URLs
    
    config.protectedSource.push(/<i[^>]*><\/i>/g);// allow i tags to be empty (for Font Awesome)
    config.protectedSource.push(/<span[^>]*?(glyphicon|icon)[^>]*><\/span>/g);// allow Bootrtap Glyphicons and ...
    
    config.fillEmptyBlocks = false; // disallows unwanted nbsp characters
};
