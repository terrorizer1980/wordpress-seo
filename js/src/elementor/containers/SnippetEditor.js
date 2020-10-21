import { compose } from "@wordpress/compose";
import { select, withSelect, withDispatch } from "@wordpress/data";
import { useEffect } from "@wordpress/element";
import { SnippetEditor } from "@yoast/search-metadata-previews";
import { __ } from "@wordpress/i18n";
import PropTypes from "prop-types";
import { LocationConsumer } from "../../components/contexts/location";
import SnippetPreviewSection from "../../components/SnippetPreviewSection";
import { applyReplaceUsingPlugin } from "../../helpers/replacementVariableHelpers";
import withLocation from "../../helpers/withLocation";

/**
 * Process the snippet editor form data before it's being displayed in the snippet preview.
 *
 * @param {Object} data                     The snippet preview data object.
 * @param {string} data.title               The snippet preview title.
 * @param {string} data.url                 The snippet preview url: baseUrl with the slug.
 * @param {string} data.description         The snippet preview description.
 * @param {Object} context                  The context surrounding the snippet editor form data.
 * @param {string} context.shortenedBaseUrl The baseUrl of the snippet preview url.
 *
 * @returns {Object} The snippet preview data object.
 */
export const mapEditorDataToPreview = ( data, context ) => {
	const templates = select( "yoast-seo/editor" ).getSnippetEditorTemplates();

	// When the editor data is empty, use the templates in the preview.
	if ( data.title === "" ) {
		data.title = templates.title;
	}
	if ( data.description === "" ) {
		data.description = templates.description;
	}

	let baseUrlLength = 0;

	if ( context.shortenedBaseUrl && typeof( context.shortenedBaseUrl ) === "string" ) {
		baseUrlLength = context.shortenedBaseUrl.length;
	}

	// Replace whitespaces in the url with dashes.
	data.url = data.url.replace( /\s+/g, "-" );
	if ( data.url[ data.url.length - 1 ] === "-" ) {
		data.url = data.url.slice( 0, -1 );
	}
	// If the first symbol after the baseUrl is a hyphen, remove that hyphen.
	// This hyphen is removed because it is usually the result of the regex replacing a space it shouldn't.
	if ( data.url[ baseUrlLength ] === "-" ) {
		data.url = data.url.slice( 0, baseUrlLength ) + data.url.slice( baseUrlLength + 1 );
	}

	return applyReplaceUsingPlugin( data );
};

/**
 * Wraps a Snippet editor with a Snippet preview section.
 *
 * @param {object} props The props.
 *
 * @returns {wp.Element} The component.
 */
const SnippetEditorWrapper = ( {  isLoading, onLoad, hasPaperStyle, location, ...restProps } ) => {
	useEffect( () => {
		setTimeout( () => {
			if ( isLoading ) {
				onLoad();
			}
		} );
	} );

	if ( isLoading ) {
		return null;
	}

	return (
		<SnippetPreviewSection
			icon="eye"
			hasPaperStyle={ hasPaperStyle }
		>
			<SnippetEditor
				{ ...restProps }
				descriptionPlaceholder={ __( "Please provide a meta description by editing the snippet below.", "wordpress-seo" ) }
				mapEditorDataToPreview={ mapEditorDataToPreview }
				showCloseButton={ false }
				idSuffix={ location }
			/>
		</SnippetPreviewSection>
	);
};

SnippetEditorWrapper.propTypes = {
	isLoading: PropTypes.bool.isRequired,
	onLoad: PropTypes.func.isRequired,
	hasPaperStyle: PropTypes.bool.isRequired,
	location: PropTypes.string.isRequired,
};

export default compose( [
	withSelect( select => {
		const {
			getBaseUrlFromSettings,
			getDateFromSettings,
			getEditorDataImageUrl,
			getFocusKeyphrase,
			getRecommendedReplaceVars,
			getReplaceVars,
			getSiteIconUrlFromSettings,
			getSnippetEditorData,
			getSnippetEditorIsLoading,
			getSnippetEditorMode,
			getSnippetEditorWordsToHighlight,
		} = select( "yoast-seo/editor" );

		const replacementVariables = getReplaceVars();

		// Replace all empty values with %%replaceVarName%% so the replacement variables plugin can do its job.
		replacementVariables.forEach( ( replaceVariable ) => {
			if ( replaceVariable.value === "" && ! [ "title", "excerpt", "excerpt_only" ].includes( replaceVariable.name ) ) {
				replaceVariable.value = "%%" + replaceVariable.name + "%%";
			}
		} );

		return {
			baseUrl: getBaseUrlFromSettings(),
			data: getSnippetEditorData(),
			date: getDateFromSettings(),
			faviconSrc: getSiteIconUrlFromSettings(),
			isLoading: getSnippetEditorIsLoading(),
			keyword: getFocusKeyphrase(),
			mobileImageSrc: getEditorDataImageUrl(),
			mode: getSnippetEditorMode(),
			recommendedReplacementVariables: getRecommendedReplaceVars(),
			replacementVariables,
			wordsToHighlight: getSnippetEditorWordsToHighlight(),
		};
	} ),
	withDispatch( dispatch => {
		const {
			updateData,
			switchMode,
			updateAnalysisData,
			loadSnippetEditorData,
		} = dispatch( "yoast-seo/editor" );

		return {
			onChange: ( key, value ) => {
				switch ( key ) {
					case "mode":
						switchMode( value );
						break;
					case "slug":
						updateData( { slug: value } );
						break;
					default:
						updateData( {
							[ key ]: value,
						} );
						break;
				}
			},
			onChangeAnalysisData: analysisData => {
				updateAnalysisData( analysisData );
			},
			onLoad: loadSnippetEditorData,
		};
	} ),
	withLocation(),
] )( SnippetEditorWrapper );