/**
 * Preferences are stored in a one-dimensional object. If storage of an object
 * is required (which it shouldn't be), it's up to the app storing the value
 * to access properties of that preference.
 */
class Preferences {
    
    constructor() {
        this.db = localStorage || window.localStorage;
        this.preferenceKey = "CobaltPreferences"
        if(!this.db) throw new Error("Your browser is too old to support client-side preferences");
    }

    get(name) {
        const prefs = this.fetchPreferences();
        const val = prefs[name];
        return val;
    }
    
    set(name, value) {
        const fetched = this.fetchPreferences();
        fetched[name] = value;
        this.storePreferences(fetched);
    }

    fetchPreferences() {
        let prefs = this.db.getItem(this.preferenceKey);
        if(!prefs) prefs = "{}"; // If prefs is null, provide an empty object.
        return JSON.parse(prefs);
    }

    storePreferences(object) {
        this.db.setItem(this.preferenceKey, JSON.stringify(object));
    }
}

window.Preferences = new Preferences();

function pref(name, value = null, isNull = false) {
    if(value !== null || isNull === true) return window.Preferences.set(name, value);
    return window.Preferences.get(name);
}
