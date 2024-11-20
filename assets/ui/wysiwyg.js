import {
	ClassicEditor,
	AccessibilityHelp,
	Autoformat,
	AutoLink,
	Autosave,
	BalloonToolbar,
	BlockQuote,
	BlockToolbar,
	Bold,
	Code,
	CodeBlock,
	Essentials,
	FindAndReplace,
    Font,
	Heading,
	Highlight,
	HorizontalLine,
	HtmlEmbed,
    Image,
    ImageCaption,
    ImageResize,
    ImageStyle,
    ImageToolbar,
    ImageUpload,
    LinkImage,
	Indent,
	IndentBlock,
	Italic,
	Link,
	Paragraph,
	SelectAll,
	SpecialCharacters,
	SpecialCharactersArrows,
	SpecialCharactersCurrency,
	SpecialCharactersEssentials,
	SpecialCharactersLatin,
	SpecialCharactersMathematical,
	SpecialCharactersText,
	Strikethrough,
	Table,
	TableCellProperties,
	TableProperties,
	TableToolbar,
	TextPartLanguage,
	TextTransformation,
	Title,
	Underline,
	Undo
} from 'ckeditor5';

import translations from 'ckeditor5/translations/fr.js';

import 'ckeditor5/ckeditor5.css';

/* import './style.css'; */
var zoneTexte = document.querySelector('textarea');




$( document ).ready( () => {
    const wysiwygs = Array.from( document.querySelectorAll( '.wysiwyg-editor' ) );

    if ( wysiwygs.length > 0 ) {
        
            wysiwygs.forEach( ( wysiwyg ) => {
                const textarea    = wysiwyg.querySelector( 'textarea' );
                textarea.required = false;
                let simpleUpload = {};

                if ( wysiwyg.getAttribute( 'data-upload' ) ) {
                    

                    simpleUpload = {
                        uploadUrl: wysiwyg.getAttribute( 'data-upload' ),
                    }
                    const colors = [
                        { color: '#ffffff', label: 'Blanc' },
                        { color: '#bcd35f', label: 'Vert' },
                        { color: '#ed7c1c', label: 'Orange' },
                        { color: '#5fbcd3', label: 'Bleu' },
                        { color: '#4d4d4dff', label: 'Gris'},
                        { color: '#bb381c', label: 'Rouge'},
                    ];
                    const editorConfig = {
                        toolbar: {
                            items: [
                                'undo',
                                'redo',
                                '|',
                                'findAndReplace',
                                'textPartLanguage',
                                '|',
                                'heading',
                                '|',
                                'fontFamily',
                                'bold',
                                'italic',
                                'underline',
                                'strikethrough',
                                'code',
                                'fontSize',
                                '|',
                                'specialCharacters',
                                'horizontalLine',
                                'link',
                                'insertTable',
                                'highlight',
                                'blockQuote',
                                'codeBlock',
                                'htmlEmbed',
                                '|',
                                'outdent',
                                'indent',
                                'fontColor', 
                                'fontBackgroundColor',
                                'insertImage',
                                
                            ],
                            shouldNotGroupWhenFull: false
                        },
                        plugins: [
                            AccessibilityHelp,
                            Autoformat,
                            AutoLink,
                            Autosave,
                            BalloonToolbar,
                            BlockQuote,
                            BlockToolbar,
                            Bold,
                            Code,
                            CodeBlock,
                            Essentials,
                            FindAndReplace,
                            Font,
                            Heading,
                            Highlight,
                            HorizontalLine,
                            HtmlEmbed,
                            Image, 
                            ImageToolbar, 
                            ImageCaption, 
                            ImageStyle, 
                            ImageResize,
                            ImageUpload, 
                            LinkImage,
                            Indent,
                            IndentBlock,
                            Italic,
                            Link,
                            MyCustomUploadAdapterPlugin,
                            Paragraph,
                            SelectAll,
                            SpecialCharacters,
                            SpecialCharactersArrows,
                            SpecialCharactersCurrency,
                            SpecialCharactersEssentials,
                            SpecialCharactersLatin,
                            SpecialCharactersMathematical,
                            SpecialCharactersText,
                            Strikethrough,
                            Table,
                            TableCellProperties,
                            TableProperties,
                            TableToolbar,
                            TextPartLanguage,
                            TextTransformation,
                            Title,
                            Underline,
                            Undo
                        ],
                        balloonToolbar: ['bold', 'italic', '|', 'link'],
                        blockToolbar: ['bold', 'italic', '|', 'link', 'insertTable', '|', 'outdent', 'indent'],
                        heading: {
                            options: [
                                {
                                    model: 'paragraph',
                                    title: 'Paragraph',
                                    class: 'ck-heading_paragraph'
                                },
                                {
                                    model: 'heading1',
                                    view: 'h1',
                                    title: 'Heading 1',
                                    class: 'ck-heading_heading1'
                                },
                                {
                                    model: 'heading2',
                                    view: 'h2',
                                    title: 'Heading 2',
                                    class: 'ck-heading_heading2'
                                },
                                {
                                    model: 'heading3',
                                    view: 'h3',
                                    title: 'Heading 3',
                                    class: 'ck-heading_heading3'
                                },
                                {
                                    model: 'heading4',
                                    view: 'h4',
                                    title: 'Heading 4',
                                    class: 'ck-heading_heading4'
                                },
                                {
                                    model: 'heading5',
                                    view: 'h5',
                                    title: 'Heading 5',
                                    class: 'ck-heading_heading5'
                                },
                                {
                                    model: 'heading6',
                                    view: 'h6',
                                    title: 'Heading 6',
                                    class: 'ck-heading_heading6'
                                }
                            ]
                        },
                        language: 'fr',
                        link: {
                            addTargetToExternalLinks: true,
                            defaultProtocol: 'https://',
                            decorators: {
                                toggleDownloadable: {
                                    mode: 'manual',
                                    label: 'Downloadable',
                                    attributes: {
                                        download: 'file'
                                    }
                                }
                            }
                        },
                        menuBar: {
                            isVisible: true
                        },
                        placeholder: 'Type or paste your content here!',
                        table: {
                            contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties']
                        },
                        translations: [translations],
                        fontColor: {
                            colors: colors,
                        },
                        fontBackgroundColor: {
                            colors: colors
                        },
                        removePlugins: ['Title'],
                        placeholder: '',
                        simpleUpload: simpleUpload,
                        height:       '500px',
                        htmlSupport: {
                            allow: [
                                {
                                    name: /.*/,
                                    attributes: true,
                                    classes: true,
                                    styles: true
                                }
                            ]
                        },
                        image: {
                            resizeUnit: 'px',
                            toolbar: [
                                'imageStyle:block',
                                'imageStyle:side',
                                '|',
                                'toggleImageCaption',
                                'imageTextAlternative',
                                '|',
                                'linkImage'
                            ]
                        },
                        fontFamily: {
                            options: [
                                'default', "Open Sans","Averia Serif Libre","Trebuchet MS","Georgia","serif"  
                            ],
                            supportAllValues: true,  
                            default: "Open Sans"  
                        },
                        fontSize: {
                            options: [ 9,10, 11,12, 13,14,15,16, 17,18, 19,20, 21 ],
                            default : 12  
                        }
                        
                    };
                    ClassicEditor.create( textarea,editorConfig);
                }

                
                    
            } );
        
    }
    

    class MyUploadAdapter {
        constructor( loader ) {
            // The file loader instance to use during the upload.
            this.loader = loader;
        }

        // Starts the upload process.
        upload() {
            return this.loader.file
                .then( file => new Promise( ( resolve, reject ) => {
                    this._initRequest();
                    this._initListeners( resolve, reject, file );
                    this._sendRequest( file );
                } ) );
        }

        // Aborts the upload process.
        abort() {
            if ( this.xhr ) {
                this.xhr.abort();
            }
        }

        // Initializes the XMLHttpRequest object using the URL passed to the constructor.
        _initRequest() {
            const xhr = this.xhr = new XMLHttpRequest();

            // Note that your request may look different. It is up to you and your editor
            // integration to choose the right communication channel. This example uses
            // a POST request with JSON as a data structure but your configuration
            // could be different.
            xhr.open( 'POST', '/image/new', true );
            xhr.responseType = 'json';
        }

        // Initializes XMLHttpRequest listeners.
        _initListeners( resolve, reject, file ) {
            const xhr = this.xhr;
            const loader = this.loader;
            const genericErrorText = `Couldn't upload file: ${ file.name }.`;

            xhr.addEventListener( 'error', () => reject( genericErrorText ) );
            xhr.addEventListener( 'abort', () => reject() );
            xhr.addEventListener( 'load', () => {
                const response = xhr.response;

                // This example assumes the XHR server's "response" object will come with
                // an "error" which has its own "message" that can be passed to reject()
                // in the upload promise.
                //
                // Your integration may handle upload errors in a different way so make sure
                // it is done properly. The reject() function must be called when the upload fails.
                if ( !response || response.error ) {
                    return reject( response && response.error ? response.error.message : genericErrorText );
                }

                // If the upload is successful, resolve the upload promise with an object containing
                // at least the "default" URL, pointing to the image on the server.
                // This URL will be used to display the image in the content. Learn more in the
                // UploadAdapter#upload documentation.
                resolve( {
                    default: response.url
                } );
            } );

            // Upload progress when it is supported. The file loader has the #uploadTotal and #uploaded
            // properties which are used e.g. to display the upload progress bar in the editor
            // user interface.
            if ( xhr.upload ) {
                xhr.upload.addEventListener( 'progress', evt => {
                    if ( evt.lengthComputable ) {
                        loader.uploadTotal = evt.total;
                        loader.uploaded = evt.loaded;
                    }
                } );
            }
        }

        // Prepares the data and sends the request.
        _sendRequest( file ) {
            // Prepare the form data.
            const data = new FormData();

            data.append( 'upload', file );

            // Important note: This is the right place to implement security mechanisms
            // like authentication and CSRF protection. For instance, you can use
            // XMLHttpRequest.setRequestHeader() to set the request headers containing
            // the CSRF token generated earlier by your application.

            // Send the request.
            this.xhr.send( data );
        }
    }

    function MyCustomUploadAdapterPlugin( editor ) {
        editor.plugins.get( 'FileRepository' ).createUploadAdapter = ( loader ) => {
            // Configure the URL to the upload script in your back-end here!
            return new MyUploadAdapter( loader );
        };
    }

    var images = $('figure img');
    if (images){
        images.each(function() {
            
            $(this).removeAttr( "width" );
            $(this).removeAttr( "height" );
        });
    }
} );