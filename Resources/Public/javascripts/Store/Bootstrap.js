Ext.ns("TYPO3.Devlog.Store");

// TODO: DOKU FOR TYPO3.Devlog.Store;

TYPO3.Devlog.Store.Bootstrap = Ext.apply(new TYPO3.Devlog.Application.AbstractBootstrap, {
	initialize: function() {
		TYPO3.Devlog.Application.on('TYPO3.Devlog.Application.afterBootstrap', this.initStore, this);
	},
	initStore: function() {
		// Ext Direct integration
//		for (var api in Ext.app.ExtDirectAPI) {
//			Ext.Direct.addProvider(Ext.app.ExtDirectAPI[api]);
//		}
//		TYPO3.Devlog.LogStore = TYPO3.Devlog.initLogDirectStore()
		TYPO3.Devlog.LogStore = TYPO3.Devlog.initLogJsonStore()
		TYPO3.Devlog.FilterByTimeStore = TYPO3.Devlog.initFilterByTimeArrayStore()
	}
});

TYPO3.Devlog.Application.registerBootstrap(TYPO3.Devlog.Store.Bootstrap);