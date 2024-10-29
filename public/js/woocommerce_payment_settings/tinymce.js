tinymce.init({
	selector: '.tinymce',
	branding: false,
	promotion: false,
	plugins: "advlist anchor autolink autoresize autosave charmap code codesample directionality emoticons fullscreen help image importcss insertdatetime link lists nonbreaking pagebreak preview quickbars save searchreplace table template visualblocks visualchars wordcount",
	editimage_cors_hosts: ['picsum.photos'],
	menubar: '',
	toolbar: 'undo redo bold italic underline strikethrough alignleft aligncenter alignright alignjustify outdent indent numlist bullist | fontfamily fontsize blocks | forecolor backcolor removeformat charmap emoticons image template link anchor codesample ltr rtl',
	font_family_formats: 'Andale Mono=andale mono,times; Arial=arial,helvetica,sans-serif; Arial Black=arial black,avant garde; Book Antiqua=book antiqua,palatino; Calibri; Comic Sans MS=comic sans ms,sans-serif; Courier New=courier new,courier; Georgia=georgia,palatino; Helvetica=helvetica; Impact=impact,chicago; Symbol=symbol; Tahoma=tahoma,arial,helvetica,sans-serif; Terminal=terminal,monaco; Times New Roman=times new roman,times; Trebuchet MS=trebuchet ms,geneva; Verdana=verdana,geneva; Webdings=webdings; Wingdings=wingdings,zapf dingbats; 新細明體, 細明體, sans; 標楷體, sans; 微軟正黑體, sans;',
	font_size_formats: '8pt 9pt 10pt 10.5pt 11pt 12pt 14pt 16pt 18pt 20pt 22pt 24pt 26pt 28pt 32pt 36pt 40pt 44pt 48pt 54pt 60pt 66pt 72pt 80pt 88pt 96pt',
	line_height_formats: '1 1.1 1.2 1.3 1.4 1.5 1.6 1.7 1.8 1.9 2 2.1 2.2 2.3 2.4 2.5 3 4',
	toolbar_sticky: false,
	autosave_ask_before_unload: true,
	autosave_interval: '10s',
	image_advtab: true,
	image_caption: true,
	quickbars_selection_toolbar: 'bold italic underline strikethrough alignleft aligncenter alignright alignjustify quicklink h2 h3 blockquote quickimage quicktable',
	noneditable_class: 'mceNonEditable',
	toolbar_mode: 'wrap',
	contextmenu: 'link image table',
	content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }',
	autoresize: true,
	min_height: 450,
	max_height: 450,
	width: '100%',
	language: document.getElementById('wp_language').value
});