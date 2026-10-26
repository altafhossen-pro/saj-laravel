'use client';
import React, { useEffect, useRef, useState, useId } from 'react';
// import { useTranslations } from 'next-intl';

interface TinyMCELocalEditorProps {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    label?: string;
    required?: boolean;
    className?: string;
    onValidationChange?: (isValid: boolean) => void;
}

const TinyMCELocalEditor: React.FC<TinyMCELocalEditorProps> = ({
    value,
    onChange,
    placeholder = "Enter your text here...",
    label,
    required = false,
    className = "",
    onValidationChange
}) => {
    // const t = useTranslations();
    const reactId = useId();
    const editorId = reactId.replace(/:/g, '');
    const editorRef = useRef<HTMLTextAreaElement>(null);
    const [isLoaded, setIsLoaded] = useState(false);
    const [isInitialized, setIsInitialized] = useState(false);
    const lastContentRef = useRef<string>(value);
    const isUpdatingRef = useRef(false);

    useEffect(() => {
        if (onValidationChange) {
            const hasContent = () => {
                if (!value || value.trim() === '') {
                    return false;
                }
                if (value === '<p></p>' || value === '<p><br></p>') {
                    return false;
                }

                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = value;
                const textContent = tempDiv.textContent || tempDiv.innerText || '';
                return textContent.trim().length > 0;
            };

            onValidationChange(hasContent());
        }
    }, [value, onValidationChange]);

    // Load TinyMCE scripts and styles
    useEffect(() => {
        const loadTinyMCE = () => {
            if (window.tinymce) {
                setIsLoaded(true);
                return;
            }

            const tinyMCECSS = document.createElement('link');
            tinyMCECSS.rel = 'stylesheet';
            tinyMCECSS.href = '/libs/tinymce/skins/ui/oxide/skin.min.css';
            document.head.appendChild(tinyMCECSS);

            const jqueryScript = document.createElement('script');
            jqueryScript.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
            jqueryScript.onload = () => {
                const tinyMCEScript = document.createElement('script');
                tinyMCEScript.src = '/libs/tinymce/jquery.tinymce.min.js';
                tinyMCEScript.onload = () => {
                    const tinyMCECoreScript = document.createElement('script');
                    tinyMCECoreScript.src = '/libs/tinymce/tinymce.min.js';
                    tinyMCECoreScript.onload = () => {
                        setIsLoaded(true);
                    };
                    document.head.appendChild(tinyMCECoreScript);
                };
                document.head.appendChild(tinyMCEScript);
            };
            document.head.appendChild(jqueryScript);
        };

        loadTinyMCE();
    }, []);

    // Initialize TinyMCE when loaded
    useEffect(() => {
        if (!isLoaded || !editorRef.current || isInitialized) return;

        const initTinyMCE = () => {
            if (window.tinymce && window.$ && editorRef.current) {
                window.$(editorRef.current).tinymce({
                    height: 300,
                    menubar: false,
                    plugins: [
                        'advlist autolink lists link image charmap print preview anchor',
                        'searchreplace visualblocks code fullscreen',
                        'insertdatetime table paste code help wordcount'
                    ],
                    toolbar: [
                        'undo redo | formatselect | fontselect | fontsizeselect',
                        'bold italic underline strikethrough | forecolor backcolor',
                        'alignleft aligncenter alignright alignjustify',
                        'bullist numlist outdent indent | link image table',
                        'removeformat | help'
                    ].join(' | '),
                    toolbar_mode: 'sliding',
                    content_style: `
                        body { 
                            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
                            font-size: 14px; 
                            line-height: 1.6;
                        }
                        table {
                            border-collapse: collapse;
                            width: 100%;
                            margin: 1rem 0;
                        }
                        th, td {
                            padding: 8px 12px;
                            text-align: left;
                        }
                        th {
                            background-color: #f9fafb;
                            font-weight: 600;
                        }
                        p {
                            margin: 0 0 1rem 0;
                        }
                        p:last-child {
                            margin-bottom: 0;
                        }
                        ul, ol {
                            margin: 0 0 1rem 0;
                            padding-left: 1.5rem;
                        }
                        h1, h2, h3, h4, h5, h6 {
                            margin: 1rem 0 0.5rem 0;
                            font-weight: 600;
                        }
                        h1:first-child, h2:first-child, h3:first-child, h4:first-child, h5:first-child, h6:first-child {
                            margin-top: 0;
                        }
                    `,
                    font_formats: 'Arial=arial,helvetica,sans-serif; Courier New=courier new,courier,monospace; Georgia=georgia,palatino; Helvetica=helvetica; Times New Roman=times new roman,times; Verdana=verdana,geneva',
                    fontsize_formats: '8pt 10pt 12pt 14pt 16pt 18pt 24pt 36pt 48pt',
                    link_assume_external_targets: true,
                    link_context_toolbar: true,
                    image_advtab: true,
                    image_upload_handler: (blobInfo: any, success: any, failure: any) => {
                        const reader = new FileReader();
                        reader.onload = () => {
                            const base64 = reader.result as string;
                            success(base64);
                        };
                        reader.onerror = () => {
                            failure('Image upload failed');
                        };
                        reader.readAsDataURL(blobInfo.blob());
                    },
                    table_default_attributes: {
                        border: '1',
                        cellpadding: '8',
                        cellspacing: '0'
                    },
                    table_default_styles: {
                        'border-collapse': 'collapse',
                        'width': '100%'
                    },
                    automatic_uploads: true,
                    file_picker_types: 'image media',
                    placeholder: placeholder,
                    setup: (editor: any) => {
                        editor.on('init', () => {
                            // Set initial content
                            if (value && value.trim() !== '') {
                                editor.setContent(value);
                                lastContentRef.current = value;
                            }
                            setIsInitialized(true);
                        });

                        // Handle content changes
                        const handleContentChange = () => {
                            if (isUpdatingRef.current) return;

                            const content = editor.getContent();

                            // Only trigger onChange if content actually changed
                            if (content !== lastContentRef.current) {
                                lastContentRef.current = content;
                                onChange(content);
                            }
                        };

                        // Attach event handlers
                        editor.on('change keyup paste cut undo redo', handleContentChange);
                        editor.on('NodeChange', handleContentChange);
                    }
                });
            }
        };

        const timer = setTimeout(initTinyMCE, 100);
        return () => {
            clearTimeout(timer);
        };
    }, [isLoaded, value, onChange]);

    // Update editor content when value prop changes externally
    useEffect(() => {
        if (window.tinymce && editorRef.current && isInitialized) {
            const editor = window.tinymce.get(editorRef.current.id);
            if (editor) {
                const currentContent = editor.getContent();

                // Only update if the value is different and not empty
                if (value !== currentContent && value !== lastContentRef.current) {
                    isUpdatingRef.current = true;
                    editor.setContent(value || '');
                    lastContentRef.current = value;

                    setTimeout(() => {
                        isUpdatingRef.current = false;
                    }, 100);
                }
            }
        }
    }, [value, isInitialized]);

    // Cleanup
    useEffect(() => {
        return () => {
            if (window.tinymce && editorRef.current) {
                const editor = window.tinymce.get(editorRef.current.id);
                if (editor) {
                    editor.destroy();
                }
            }
        };
    }, []);

    return (
        <div className={`space-y-2 ${className}`}>
            {label && (
                <label className="block text-sm font-medium text-gray-700">
                    {label} {required && <span className="text-red-500">*</span>}
                </label>
            )}

            <div className="border border-gray-300 rounded-lg overflow-hidden">
                <div className="bg-white">
                    {!isLoaded && (
                        <div className="p-4 text-center text-gray-500">
                            {/* {t('common.loading_editor')} */} Loading editor...
                        </div>
                    )}
                    <textarea
                        ref={editorRef}
                        id={`tinymce-editor-${editorId}`}
                        placeholder={placeholder}
                        style={{ minHeight: '300px' }}
                        className="w-full border-0 focus:ring-0 focus:outline-none"
                    />
                </div>
            </div>
        </div>
    );
};

declare global {
    interface Window {
        tinymce: any;
        $: any;
    }
}

export default TinyMCELocalEditor;