//-----------------------------Start Plugin Code-------------------------



plugInName = 'htmlSource';

CKEDITOR.plugins.add(plugInName,
{  
  init: function (editor) {

    editor.addCommand('htmlDialog', new CKEDITOR.dialogCommand('htmlDialog'));
    editor.ui.addButton(plugInName, {
	label:editor.lang.sourcearea.toolbar,
        icon: this.path + 'images/source.png',	// uavster : Changed image path
        command: 'htmlDialog'
    });

    CKEDITOR.dialog.add('htmlDialog', function (editor) {
        return {
            title: editor.lang.sourcearea.toolbar,
            minWidth: 600,
            minHeight: 400,
            contents: [
                        {
                            id: 'general',
                            label: 'Settings',
                            elements:
                            [
                            // UI elements of the Settings tab.
                                {
                                type: 'textarea',
                                id: 'contents',
                                rows: 25,
                                onShow: function () {
//                                    this.setValue(editor.container.$.innerHTML);	// uavster: Original code
				    this.setValue(editor.getData());			// uavster: Compliant with the documentation

                                },
                                commit: function (data) {              //--I get only the body part in case I paste a complete html
                                    data.contents = this.getValue().replace(/^[\S\s]*<body[^>]*?>/i, "").replace(/<\/body[\S\s]*$/i, "");
                                }

                            }
                                ]
                        }
                    ],

            onOk: function () {
                var data = {};
                this.commitContent(data);
//                $(editor.container.$).html(data.contents);	// uavster : This was the original code which doesn't work
//                editor.container.$.innerHTML = data.contents;	// uavster : This works. Iframes are loaded right after pasting as HTML, but are not loaded after reopening the editor after saving.
		  editor.setData(data.contents);		// uavster : This works. Iframes are not loaded neither after pasting the HTML code nor after reopening the editor after saving.
            },
            onCancel: function () {
                //  console.log('Cancel');
            }
        };
    });
}


});

//--------------------Plugin Code Ends Here--------------------
