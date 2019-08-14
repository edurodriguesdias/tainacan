const { registerBlockType } = wp.blocks;

const { __ } = wp.i18n;

const { RangeControl, Spinner, Button, ToggleControl, SelectControl, Placeholder, IconButton, PanelBody } = wp.components;

const { InspectorControls } = wp.editor;

import CarouselCollectionsModal from './carousel-collections-modal.js';
import tainacan from '../../api-client/axios.js';
import axios from 'axios';
import qs from 'qs';

registerBlockType('tainacan/carousel-collections-list', {
    title: __('Tainacan Collections Carousel', 'tainacan'),
    icon:
        <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                height="24px"
                width="24px">
            <path
                fill="#298596"
                d="M18,17v2H12a5.65,5.65,0,0,0-.36-2ZM2,7v7.57a5.74,5.74,0,0,1,2-1.2V7ZM20,6H15L13,4H8A2,2,0,0,0,6,6v7a6,6,0,0,1,5.19,3H20a2,2,0,0,0,2-2V8A2,2,0,0,0,20,6ZM7,16.05v6.06l3.06-3.06ZM5,22.11V16.05L1.94,19.11Z"/>
        </svg>,
    category: 'tainacan-blocks',
    keywords: [ __( 'collections', 'tainacan' ), __( 'carousel', 'tainacan' ), __( 'slider', 'tainacan' ) ],
    attributes: {
        content: {
            type: 'array',
            source: 'children',
            selector: 'div'
        },
        collections: {
            type: Array,
            default: []
        },
        isModalOpen: {
            type: Boolean,
            default: false
        },
        selectedCollections: {
            type: Array,
            default: []
        },
        itemsRequestSource: {
            type: String,
            default: undefined
        },
        maxCollectionsNumber: {
            type: Number,
            value: undefined
        },
        isLoading: {
            type: Boolean,
            value: false
        },
        isLoadingCollection: {
            type: Boolean,
            value: false
        },
        arrowsPosition: {
            type: String,
            value: 'search'
        },
        autoPlay: {
            type: Boolean,
            value: false
        },
        autoPlaySpeed: {
            type: Number,
            value: 3
        },
        loopSlides: {
            type: Boolean,
            value: false
        },
        hideName: {
            type: Boolean,
            value: true
        },
        showCollectionHeader: {
            type: Boolean,
            value: false
        },
        showCollectionLabel: {
            type: Boolean,
            value: false
        },
        collection: {
            type: Object,
            value: undefined
        },
        blockId: {
            type: String,
            default: undefined
        },
        collectionBackgroundColor: {
            type: String,
            default: "#454647"
        },
        collectionTextColor: {
            type: String,
            default: "#ffffff"
        },
        extraParams: {
            type: Object,
            default: {}
        }
    },
    supports: {
        align: ['full', 'wide'],
        html: false,
        multiple: false
    },
    edit({ attributes, setAttributes, className, isSelected, clientId }){
        let {
            collections, 
            content, 
            isModalOpen,
            itemsRequestSource,
            selectedCollections,
            isLoading,
            arrowsPosition,
            autoPlay,
            autoPlaySpeed,
            loopSlides,
            hideName
        } = attributes;

        // Obtains block's client id to render it on save function
        setAttributes({ blockId: clientId });
        
        function prepareItem(collection) {
            return (
                <li 
                    key={ collection.id }
                    className="collection-list-item">   
                    <IconButton
                        onClick={ () => removeItemOfId(collection.id) }
                        icon="no-alt"
                        label={__('Remove', 'tainacan')}/>
                    <a 
                        id={ isNaN(collection.id) ? collection.id : 'collection-id-' + collection.id }
                        href={ collection.url } 
                        target="_blank">
                        <img
                            src={ 
                                collection.thumbnail && collection.thumbnail['tainacan-medium'][0] && collection.thumbnail['tainacan-medium'][0] 
                                    ?
                                collection.thumbnail['tainacan-medium'][0] 
                                    :
                                (collection.thumbnail && collection.thumbnail['thumbnail'][0] && collection.thumbnail['thumbnail'][0]
                                    ?    
                                collection.thumbnail['thumbnail'][0] 
                                    : 
                                `${tainacan_plugin.base_url}/admin/images/placeholder_square.png`)
                            }
                            alt={ collection.name ? collection.name : __( 'Thumbnail', 'tainacan' ) }/>
                        { !hideName ? <span>{ collection.name ? collection.name : '' }</span> : null }
                    </a>
                </li>
            );
        }

        function setContent(){
            isLoading = true;

            setAttributes({
                isLoading: isLoading
            });

            if (itemsRequestSource != undefined && typeof itemsRequestSource == 'function')
                itemsRequestSource.cancel('Previous collections search canceled.');

            itemsRequestSource = axios.CancelToken.source();

            collections = [];

            let endpoint = '/collections?'+ qs.stringify({ postin: selectedCollections }) + '&fetch_only=name,url,thumbnail';
            tainacan.get(endpoint, { cancelToken: itemsRequestSource.token })
                .then(response => {

                    for (let collection of response.data)
                        collections.push(prepareItem(collection));

                    setAttributes({
                        content: <div></div>,
                        collections: collections,
                        isLoading: false,
                        itemsRequestSource: itemsRequestSource
                    });
                });
            
        }

        function openCarouselModal() {
            isModalOpen = true;
            setAttributes( { 
                isModalOpen: isModalOpen
            } );
        }

        function removeItemOfId(itemId) {

            let existingItemIndex = collections.findIndex((existingItem) => existingItem.key == itemId);
            if (existingItemIndex >= 0)
                collections.splice(existingItemIndex, 1);

            let existingSelectedItemIndex = selectedCollections.findIndex((existingSelectedItem) => existingSelectedItem == itemId);
            if (existingSelectedItemIndex >= 0)
                selectedCollections.splice(existingSelectedItemIndex, 1);
        
            setAttributes({ 
                selectedCollections: selectedCollections,
                collections: collections,
                content: <div></div> 
            });
        }

        // Executed only on the first load of page
        if(content && content.length && content[0].type)
            setContent();

        return (
            <div className={className}>

                <div>
                    <InspectorControls>

                        <PanelBody
                                title={__('Carousel', 'tainacan')}
                                initialOpen={ true }
                            >
                            <div>
                                <ToggleControl
                                        label={__('Hide name', 'tainacan')}
                                        help={ !hideName ? __('Toggle to hide collection\'s name', 'tainacan') : __('Do not hide collection\'s name', 'tainacan')}
                                        checked={ hideName }
                                        onChange={ ( isChecked ) => {
                                                hideName = isChecked;
                                                setAttributes({ hideName: hideName });
                                                setContent();
                                            } 
                                        }
                                    />
                                <ToggleControl
                                        label={__('Loop slides', 'tainacan')}
                                        help={ !loopSlides ? __('Toggle to make slides loop from first to last', 'tainacan') : __('Do not loop slides from first to last', 'tainacan')}
                                        checked={ loopSlides }
                                        onChange={ ( isChecked ) => {
                                                loopSlides = isChecked;
                                                setAttributes({ loopSlides: loopSlides });
                                            } 
                                        }
                                    />
                                <ToggleControl
                                        label={__('Auto play', 'tainacan')}
                                        help={ !autoPlay ? __('Toggle to automatically slide to next collection', 'tainacan') : __('Do not automatically slide to next collection', 'tainacan')}
                                        checked={ autoPlay }
                                        onChange={ ( isChecked ) => {
                                                autoPlay = isChecked;
                                                setAttributes({ autoPlay: autoPlay });
                                            } 
                                        }
                                    />
                                { 
                                    autoPlay ? 
                                        <RangeControl
                                            label={__('Seconds before translating to next', 'tainacan')}
                                            value={ autoPlaySpeed }
                                            onChange={ ( aAutoPlaySpeed ) => {
                                                autoPlaySpeed = aAutoPlaySpeed;
                                                setAttributes( { autoPlaySpeed: aAutoPlaySpeed } ) 
                                            }}
                                            min={ 1 }
                                            max={ 5 }
                                        />
                                    : null
                                }
                                <SelectControl
                                    label={__('Arrows', 'tainacan')}
                                    value={ arrowsPosition }
                                    options={ [
                                        { label: __('Around', 'tainacan'), value: 'around' },
                                        { label: __('Left', 'tainacan'), value: 'left' },
                                        { label: __('Right', 'tainacan'), value: 'right' }
                                    ] }
                                    onChange={ ( aPosition ) => { 
                                        arrowsPosition = aPosition;

                                        setAttributes({ arrowsPosition: arrowsPosition }); 
                                    }}/>
                            </div>                           
                        </PanelBody>
                    </InspectorControls>
                </div>

                { isSelected ? 
                    (
                    <div>
                        { isModalOpen ? 
                            <CarouselCollectionsModal
                                selectedCollectionsObject={ selectedCollections }
                                onApplySelection={ (aSelectionOfCollections) => {
                                    selectedCollections = selectedCollections.concat(aSelectionOfCollections.map((collection) => { return collection.id; })); 
                                    setAttributes({
                                        selectedCollections: selectedCollections,
                                        isModalOpen: false
                                    });
                                    setContent();
                                }}
                                onCancelSelection={ () => setAttributes({ isModalOpen: false }) }/> 
                            : null
                        }
                        
                        { collections.length ? (
                            <div className="block-control">
                                <p>
                                    <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            height="24px"
                                            width="24px">
                                        <path d="M18,17v2H12a5.65,5.65,0,0,0-.36-2ZM2,7v7.57a5.74,5.74,0,0,1,2-1.2V7ZM20,6H15L13,4H8A2,2,0,0,0,6,6v7a6,6,0,0,1,5.19,3H20a2,2,0,0,0,2-2V8A2,2,0,0,0,20,6ZM7,16.05v6.06l3.06-3.06ZM5,22.11V16.05L1.94,19.11Z"/>
                                    </svg>
                                    {__('List collections on a Carousel', 'tainacan')}
                                </p>
                                <Button
                                    isPrimary
                                    type="submit"
                                    onClick={ () => openCarouselModal() }>
                                    {__('Add more collections', 'tainacan')}
                                </Button> 
                            </div>
                            ): null
                        }
                    </div>
                    ) : null
                }

                { !collections.length && !isLoading ? (
                    <Placeholder
                        icon={(
                            <img
                                width={148}
                                src={ `${tainacan_plugin.base_url}/admin/images/tainacan_logo_header.svg` }
                                alt="Tainacan Logo"/>
                        )}>
                        <p>
                            <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    height="24px"
                                    width="24px">
                                <path d="M18,17v2H12a5.65,5.65,0,0,0-.36-2ZM2,7v7.57a5.74,5.74,0,0,1,2-1.2V7ZM20,6H15L13,4H8A2,2,0,0,0,6,6v7a6,6,0,0,1,5.19,3H20a2,2,0,0,0,2-2V8A2,2,0,0,0,20,6ZM7,16.05v6.06l3.06-3.06ZM5,22.11V16.05L1.94,19.11Z"/>
                            </svg>
                            {__('List collections on a Carousel, using search or collection selection.', 'tainacan')}
                        </p>
                        <Button
                            isPrimary
                            type="submit"
                            onClick={ () => openCarouselModal() }>
                            {__('Select Collections', 'tainacan')}
                        </Button>   
                    </Placeholder>
                    ) : null
                }
                
                { isLoading ? 
                    <div class="spinner-container">
                        <Spinner />
                    </div> :
                    <div>
                        { isSelected && collections.length ? 
                            <div class="preview-warning">{__('Warning: this is just a demonstration. To see the carousel in action, either preview or publish your post.', 'tainacan')}</div>
                            : null
                        }
                        {  collections.length ? (
                            <div
                                    className={'collections-list-edit-container has-arrows-' + arrowsPosition}>
                                <button 
                                        class="swiper-button-prev" 
                                        slot="button-prev"
                                        style={{ cursor: 'not-allowed' }}>
                                    <svg
                                            width="42"
                                            height="42"
                                            viewBox="0 0 24 24">
                                        <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
                                        <path
                                                d="M0 0h24v24H0z"
                                                fill="none"/>                         
                                    </svg>
                                </button>
                                <ul className={'collections-list-edit'}>
                                    { collections }
                                </ul>
                                <button 
                                        class="swiper-button-next" 
                                        slot="button-next"
                                        style={{ cursor: 'not-allowed' }}>
                                    <svg
                                            width="42"
                                            height="42"
                                            viewBox="0 0 24 24">
                                        <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
                                        <path
                                                d="M0 0h24v24H0z"
                                                fill="none"/>                        
                                    </svg>
                                </button>
                            </div>
                        ):null
                        }
                    </div>
                }
            </div>
        );
    },
    save({ attributes, className }){
        const {
            content, 
            blockId,
            selectedCollections,
            arrowsPosition,
            maxCollectionsNumber,
            autoPlay,
            autoPlaySpeed,
            loopSlides,
            hideName,
            extraParams
        } = attributes;
        return <div 
                    className={ className }
                    selected-collections={ JSON.stringify(selectedCollections) }
                    arrows-position={ arrowsPosition }
                    auto-play={ '' + autoPlay }
                    auto-play-speed={ autoPlaySpeed }
                    loop-slides={ '' + loopSlides }
                    hide-name={ '' + hideName }
                    max-collections-number={ maxCollectionsNumber }
                    tainacan-api-root={ tainacan_plugin.root }
                    tainacan-base-url={ tainacan_plugin.base_url }
                    extraParams={ JSON.stringify(extraParams)  }
                    id={ 'wp-block-tainacan-carousel-collections-list_' + blockId }>
                        { content }
                </div>
    }
});