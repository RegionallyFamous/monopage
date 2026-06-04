<?php
/**
 * Title: Pricing Deck
 * Slug: monopage-canvas/pricing-deck
 * Categories: monopage-canvas
 * Description: A three-plan pricing section with a featured middle card.
 * Keywords: monopage, pricing, plans, comparison
 * Viewport Width: 1180
 *
 * @package Monopage_Canvas
 */
?>

<!-- wp:group {"tagName":"section","align":"full","anchor":"pricing-deck","className":"monopage-section monopage-pricing","layout":{"type":"constrained","contentSize":"1180px"}} -->
<section class="wp-block-group alignfull monopage-section monopage-pricing" id="pricing-deck">
	<!-- wp:group {"align":"wide","className":"monopage-section-heading","layout":{"type":"constrained","contentSize":"760px"}} -->
	<div class="wp-block-group alignwide monopage-section-heading">
		<!-- wp:paragraph {"align":"center","className":"monopage-kicker"} -->
		<p class="has-text-align-center monopage-kicker">Pricing deck</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"textAlign":"center","fontSize":"section-title","style":{"typography":{"lineHeight":"1.04"}}} -->
		<h2 class="wp-block-heading has-text-align-center has-section-title-font-size" style="line-height:1.04">Give visitors a decision, not a spreadsheet.</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center"} -->
		<p class="has-text-align-center">Three clear paths, a featured recommendation, and buttons that stay on the same page.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"align":"wide","className":"monopage-pricing-grid","style":{"spacing":{"blockGap":{"top":"1rem","left":"1rem"}}}} -->
	<div class="wp-block-columns alignwide monopage-pricing-grid">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"monopage-price-card","layout":{"type":"constrained"}} -->
			<div class="wp-block-group monopage-price-card">
				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading">Sprint</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"className":"monopage-price"} -->
				<p class="monopage-price">$900</p>
				<!-- /wp:paragraph -->

				<!-- wp:list {"className":"monopage-check-list"} -->
				<ul class="wp-block-list monopage-check-list">
					<li>One focused offer</li>
					<li>Starter proof section</li>
					<li>Launch-ready CTA</li>
				</ul>
				<!-- /wp:list -->

				<!-- wp:buttons -->
				<div class="wp-block-buttons">
					<!-- wp:button {"className":"is-style-outline"} -->
					<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#start">Pick Sprint</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"monopage-price-card is-featured","layout":{"type":"constrained"}} -->
			<div class="wp-block-group monopage-price-card is-featured">
				<!-- wp:paragraph {"className":"monopage-plan-badge"} -->
				<p class="monopage-plan-badge">Best fit</p>
				<!-- /wp:paragraph -->

				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading">Campaign</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"className":"monopage-price"} -->
				<p class="monopage-price">$2.4K</p>
				<!-- /wp:paragraph -->

				<!-- wp:list {"className":"monopage-check-list"} -->
				<ul class="wp-block-list monopage-check-list">
					<li>Offer, proof, and pricing</li>
					<li>Editable section system</li>
					<li>Launch review pass</li>
				</ul>
				<!-- /wp:list -->

				<!-- wp:buttons -->
				<div class="wp-block-buttons">
					<!-- wp:button -->
					<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#start">Pick Campaign</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"monopage-price-card","layout":{"type":"constrained"}} -->
			<div class="wp-block-group monopage-price-card">
				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading">Studio</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"className":"monopage-price"} -->
				<p class="monopage-price">$5K</p>
				<!-- /wp:paragraph -->

				<!-- wp:list {"className":"monopage-check-list"} -->
				<ul class="wp-block-list monopage-check-list">
					<li>Expanded campaign page</li>
					<li>Custom visual direction</li>
					<li>Deployment workflow</li>
				</ul>
				<!-- /wp:list -->

				<!-- wp:buttons -->
				<div class="wp-block-buttons">
					<!-- wp:button {"className":"is-style-outline"} -->
					<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#start">Pick Studio</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</section>
<!-- /wp:group -->
