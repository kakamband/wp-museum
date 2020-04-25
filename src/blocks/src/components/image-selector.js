import {
	useState,
	useEffect
} from '@wordpress/element';

import apiFetch from "@wordpress/api-fetch";

import {
	IconButton,
} from '@wordpress/components'

const ImageSelector = ( props ) => {
	const {
		objectID,
		imgIndex,
		totalImages,
		imgDimensions,
		setImgData,
		imgURL,
		setImageSize
	} = props;

	const [ imageData,       updateImageData       ] = useState( null );
	const [ fetchedObjectID, updateFetchedObjectID ] = useState( null );

	const rest_path = `/wp-museum/v1/all/${objectID}/images`;

	const updateImageIndex = ( increment ) => {
		let newImgIndex = imgIndex + increment;
		if ( totalImages === 0 ) {
			newImgIndex = 0
		} else if ( newImgIndex < 0 ) {
			newImgIndex = totalImages - 1;
		} else if ( newImgIndex >= totalImages ) {
			newImgIndex = 0
		}

		setImgData( { imgIndex: newImgIndex } );
	}

	useEffect( () => {
		const bestFitImage = {
			'URL'    : null,
			'height' : 99999999,
			'width'  : 99999999
		};
		
		if ( objectID !== null ) {
			if ( objectID !== fetchedObjectID ) {
				updateFetchedObjectID( objectID );
				updateImageData( null );
			}
			if ( imageData === null ) {
				apiFetch( { path: rest_path } ).then( result => updateImageData( result ) );
			} else if ( imageData.length > 0 ) {
				const selectedImageData = imageData[ imgIndex ];
				for ( let [ sizeSlug, dataArray ] of Object.entries( selectedImageData ) ) {
					let [
						URL,
						height,
						width,
						isIntermediate
					] = dataArray;
	
					if ( height >= imgDimensions.height && 
						height <  bestFitImage.height && 
						width  >= imgDimensions.width && 
						width  <  bestFitImage.width ) {
							bestFitImage.URL    = URL;
							bestFitImage.height = height;
							bestFitImage.width  = width;
					}
				}
				if ( bestFitImage.URL === null ) {
					const [
						URL,
						height,
						width,
						isIntermediate
					] = selectedImageData['full'];
					bestFitImage.URL    = URL;
					bestFitImage.height = height;
					bestFitImage.width  = width
				}
				setImgData ( {
					imgURL      : bestFitImage.URL,
					imgHeight   : bestFitImage.height,
					imgWidth    : bestFitImage.width,
					totalImages : Object.keys( imageData ).length,
				} );
			}
		}
	} );

	const selectorStyle = {
		backgroundImage: `url('${imgURL}')`
	}

	if ( setImageSize && imgDimensions ) {
		selectorStyle.height = imgDimensions.height;
		selectorStyle.width = imgDimensions.width;
	}

	return (
		( imgURL ) ?
			<div
				className = 'image-selector-container'
				style     = { selectorStyle }
			>
				<IconButton
					className = 'left-arrow selector-button'
					icon      = 'arrow-left-alt2'
					onClick   = { () => updateImageIndex( -1 ) }
				/>
				<IconButton
					className = 'right-arrow selector-button'
					icon      = 'arrow-right-alt2'
					onClick   = { () => updateImageIndex( 1 ) }
				/>
			</div>
		:
			<div className = 'img-placeholder'></div>	
	);

	
}

ImageSelector.defaultProps = {
	setImageSize: true
}

export default ImageSelector;