import domReady from "@wordpress/dom-ready";
import { __ } from "@wordpress/i18n";
import { renderReactRoot } from "./helpers/reactRoot";
import { setWordPressSeoL10n } from "./helpers/i18n";
import { ButtonStyledLink } from "@yoast/components";

setWordPressSeoL10n();

/**
 * @returns {WPElement} The fake steppers.
 */
export default function Steppers() {
	return (
		<ul className="step-list">
			<li className={ "step-complete" }>
				<div className="step-first-div" aria-hidden="true">
					<div className="step-second-div" />
				</div>
				<span
					className="step-list-complete-circle"
				>
					{/* <CheckIcon className="w-5 h-5 text-white" aria-hidden="true" /> */}
					<span className="complete-check-icon" />
				</span>
			</li>
			<li className={ "step-current" }>
				<span
					href="#"
					className="step-list-current-circle"
					aria-current="step"
				>
					<span className="step-list-current-inner-circle" aria-hidden="true" />
				</span>
			</li>
		</ul>
	);
}


/**
 * @returns {WPElement} The installation success page.
 */
function InstallationSuccessPage() {
	return (
		<div className="installation-success-page">
			<h1 className="installation-success-title">{ __( "You've successfully installed Yoast SEO!", "wordpress-seo" ) }</h1>
			<div className="installation-success-steps">
				<Steppers />
			</div>
			<div className="installation-success-cards">
				<div className="installation-success-card">
					<h2>{ __( "Your site is now easy to find for search engines!", "wordpress-seo" ) }</h2>
					<p>
						{ __( "Yoast SEO rolls out the red carpet for the search bots, which helps your site perform better in search engines.", "wordpress-seo" ) }
					</p>
					<div className="card-button-section">
						<img
							className="man-with-tablet"
							src={ window.wpseoInstallationSuccess.pluginUrl + "/images/man_with_tablet.png" }
							alt={ __( "Man holding a tablet.", "wordpress-seo" ) }
						/>
					</div>
				</div>
				<div className="installation-success-card active">
					<h2>{ __( "Configure Yoast SEO", "wordpress-seo" ) }</h2>
					<p>
						{ __( "Set the essential Yoast SEO settings in a few steps.", "wordpress-seo" ) }
					</p>
					<img
						className="man-with-tablet"
						src={ window.wpseoInstallationSuccess.pluginUrl + "/images/man_with_tablet.png" }
						alt={ __( "Man holding a tablet.", "wordpress-seo" ) }
					/>
					<div className="card-button-section">
						<ButtonStyledLink
							id="installation-successful-configuration-workout-link"
							href={ window.wpseoInstallationSuccess.configurationWorkoutUrl }
							variant="primary"
						>
							{ __( "Start configuration workout!", "wordpress-seo" ) }
						</ButtonStyledLink>
					</div>
				</div>
			</div>
		</div>
	);
}

domReady( () => {
	renderReactRoot( "wpseo-installation-successful-free", <InstallationSuccessPage /> );
} );
