var LayersliderInit = function () {

    return {
        initLayerSlider: function () {
            $('#layerslider').layerSlider({
                skinsPath : basePath + '/assets/global/plugins/slider-layer-slider/skins/',
                skin : 'v5',
                thumbnailNavigation : 'hover',
                hoverPrevNext : false,
                responsive : false,
                responsiveUnder : 960,
                layersContainer : 960
            });
        }
    };

}();