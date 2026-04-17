import ICustomInput from "./ICustomInput";

export default class GeoPointElement extends ICustomInput {
    mapContainer = document.createElement("div");
    leafletInstance = null;
    constructor() {
        super.constructor();
        
    }
    
    get value() {
        return 
    }

    set value(value) {

    }

    connectedCallback() {
        this.appendChild(this.mapContainer);
    }

    initMap() {
        const type = this.dataset.type;
        const coordinates = JSON.parse(this.getAttribute('data-coordinates'));
        switch(type) {
            case "Point":
                this.initGeoJSONPoint(coordinates)
                break;
        }
        
    }

    initGeoJSONPoint(coordinates, zoom = 13) {
        this.leafletInstance = L.map(this.mapContainer, {
            center: coordinates,
            zoom
        });
    }
    
}