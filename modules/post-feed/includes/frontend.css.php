// css

.spigot-post-list__card {
	margin: 12px;
	padding-bottom: 30px;
	flex: 0 1 100%;
}

@media (min-width: 600px) {

	.spigot-post-list__card {
		-ms-flex-preferred-size: calc(50% - 24px);
		flex-basis: calc(50% - 24px);
		width: calc(50% - 24px);
	}
}

@media (min-width: 1200px) {
	.spigot-post-list__card {
		-ms-flex-preferred-size: calc(33.33333% - 24px);
		flex-basis: calc(33.33333% - 24px);
		width: calc(33.33333% - 24px);
	}
}

