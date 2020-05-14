$( document ).ready( () => {
    const wysiwygs = Array.from( document.querySelectorAll( '.wysiwyg-editor' ) );

    if ( wysiwygs.length > 0 ) {
        import( /* webpackChunkName: "ckeditor" */ '@ckeditor/ckeditor5-build-classic' ).then( ( { default: ClassicEditor } ) => {
            wysiwygs.forEach( ( wysiwyg ) => {
                const textarea    = wysiwyg.querySelector( 'textarea' );
                textarea.required = false;

                let toolbar = [ 'heading', '|',
                    'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|',
                    'mediaEmbed', 'insertTable', '|',
                    'undo', 'redo' ];

                let extraPlugins = [];

                function UploadAdapterPlugin( editor ) {
                    editor.plugins.get( 'FileRepository' ).createUploadAdapter = ( loader ) => {
                        const uploadURL = wysiwyg.getAttribute( 'data-upload' );

                        return new UploadAdapter( loader, uploadURL );
                    };
                }

                if ( wysiwyg.getAttribute( 'data-upload' ) ) {
                    toolbar      = [ 'heading', '|',
                        'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|',
                        'imageUpload', 'mediaEmbed', 'insertTable', '|',
                        'undo', 'redo' ];
                    extraPlugins = [ UploadAdapterPlugin ];
                }

                ClassicEditor
                    .create( textarea, {
                        toolbar:      toolbar,
                        extraPlugins: extraPlugins,
                        height:       '500px',
                    } )
                    .then( editor => {
                        console.log( Array.from( editor.ui.componentFactory.names() ) );

                        editor.on( 'required', ( evt ) => {
                            alert( 'This field is required.' );
                            evt.cancel();
                        } );
                    } )
                    .catch( error => {
                        console.error( error );
                    } );
            } );
        } );
    }
} );
