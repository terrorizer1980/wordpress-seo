import { shallow } from "enzyme";

global.window.wpseoAdminL10n = [];
global.window.wpseoAdminL10n[ "shortlinks.semrush.volume_help" ] = "test.com";

import WincherKeyphrasesTable
	from "../../../js/src/components/WincherKeyphrasesTable";
import { noop } from "lodash";
import WincherTableRow from "../../src/components/WincherTableRow";


const keyphrases = [ "yoast seo" ];

const keyphrasesData = {
	"yoast seo": {
		id: "12345",
		keyword: "yoast seo",
	},
	"woocommerce seo": {
		id: "54321",
		keyword: "woocommerce seo",
	},
};

const chartData = {
	"yoast seo": {
		id: "12345",
		keyword: "yoast seo",
		position: {
			value: 10,
			history: [
				{
					datetime: "2021-08-02T22:00:00Z",
					value: 40,
				},
				{
					datetime: "2021-08-03T22:00:00Z",
					value: 38,
				},
			],
		},
	},
};

describe( "WincherKeyphrasesTable", () => {
	it( "should fill the table with 1 element", () => {
		const component = shallow( <WincherKeyphrasesTable
			keyphrases={ keyphrases }
			onAuthentication={ noop }
			addTrackingKeyphrase={ noop }
			newRequest={ noop }
			setRequestLimitReached={ noop }
			setPendingChartRequest={ noop }
			setTrackingKeyphrases={ noop }
			setRequestFailed={ noop }
			setRequestSucceeded={ noop }
			setTrackingCharts={ noop }
			removeTrackingKeyphrase={ noop }
		/> );

		expect( component.find( "tbody" ).getElement().props.children.length ).toEqual( 1 );
	} );

	it( "should have the right keyphrases present", () => {
		const component = shallow( <WincherKeyphrasesTable
			keyphrases={ keyphrases }
			trackedKeyphrases={ keyphrasesData }
			trackedKeyphrasesChartData={ chartData }
			onAuthentication={ noop }
			addTrackingKeyphrase={ noop }
			newRequest={ noop }
			setRequestLimitReached={ noop }
			setPendingChartRequest={ noop }
			setTrackingKeyphrases={ noop }
			setRequestFailed={ noop }
			setRequestSucceeded={ noop }
			setTrackingCharts={ noop }
			removeTrackingKeyphrase={ noop }
		/> );

		expect( component.find( WincherTableRow ).length ).toEqual( 1 );

		expect( component.instance().allKeyphrasesHavePositionData() ).toEqual( false );
		expect( component.instance().getKeyphraseData( "yoast seo" ) ).toEqual( keyphrasesData[ "yoast seo" ] );
		expect( component.instance().getKeyphraseChartData( "yoast seo" ) ).toEqual( chartData[ "yoast seo" ] );
	} );
} );
