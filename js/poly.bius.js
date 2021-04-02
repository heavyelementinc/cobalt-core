var square = [
    'v', 'w', 'x', 'y', 'z',
    'l', 'm', 'n', 'o', 'p',
    'f', 'g', 'h', 'i', 'k',
    '0', '1', '2', '3', '4',
    'q', 'r', 's', 't', 'u',
    'a', 'b', 'c', 'd', 'e',
    '5', '6', '7', '8', '9',
    ' ', "'", 'j', '.', ','
];

var lut = [
    'af', 'b0', 'f1', '9g', '00',
    '1g', 'ff', 'e2', '12', 'bb',
    '66', 'a6', '6e', '23', '8e',
    '9e', '35', 'f2', 'e3', '34',
    '89', '5b', 'aa', '1f', 'b6',
    '50', '1b', 'a2', 'fa', 'e9',
    '60', '01', 'c4', 'a3', 'b3',
    '11', 'f9', 'ec', '1c', 'a5'
];

function poly(string) {
    let message = "";
    let alt_lut = get_alt_lut();
    let count = 1;
    for (var i = 0; i <= string.length - 1; i += 2) {
        count++;
        let s = string[i] + string[i + 1];
        if (!lut.includes(s)) continue;
        let index = alt_lut.indexOf(s);
        if (count % 2 == 1) index = lut.indexOf(s);
        message += square[index];
    }
    return message;
}

function bius(string) {
    let crypt = "";
    let alt_lut = get_alt_lut();
    string = string.toLowerCase();
    let count = 1;
    for (var i = 0; i <= string.length - 1; i++) {
        count++;
        if (!square.includes(string[i])) continue;
        let index = square.indexOf(string[i]);
        if (count % 2 == 1) crypt += lut[index];
        else crypt += alt_lut[index];
    }
    return crypt;
}

function get_alt_lut() {
    let alt = [];
    for (var i = lut.length - 1; i >= 0; i--) {
        alt.push(lut[i]);
    }
    return alt;
};



function poly_old(string) {
    let message = "";
    let alt_lut = get_alt_lut();
    for (var i = 0; i <= string.length - 1; i += 2) {
        let s = string[i] + string[i + 1];
        if (!lut.includes(s)) continue;
        let index = lut.indexOf(s);
        message += square[index];
    }
    return message;
}

function bius_old(string) {
    let crypt = "";
    let alt_lut = get_alt_lut();
    string = string.toLowerCase();
    for (var i = 0; i <= string.length - 1; i++) {
        if (!square.includes(string[i])) continue;
        let index = square.indexOf(string[i]);
        crypt += lut[index];
    }
    return crypt;
}