
/**
 *  fizzbuzz loop.
 */
for (let i = 0; i < 100; i++) {
    let str = '';
    if (!(i%3)) { str+= 'fizz'; }
    if (!(i%5)) { str+= 'buzz'; }
    console.log(str);
}

/**
 * Recursive russiandoll class.
 */
class RussianDoll {
    constructor (size) {
        if (!size < 11 || !size > 0) { throw new Error('RussianDoll: Invalid size. (' + size + ')'); }
        this.size = size;
    }

    placeInside (doll) {
        if (doll instanceof RussianDoll) {
            if (doll.size < this.size) {
                if (this.inside === null) {
                    this.inside = doll;
                    return true;
                } else {
                    if (doll.size < this.inside.size)
                    { return this.inside.placeInside(doll); }
                    else if (doll.size > this.inside.size) {
                        doll.placeInside(this.inside);
                        this.inside = doll;
                        return true;
                    } else { throw new Error('placeInside(): The doll passed can not be the same size as the doll already placed inside.'); }
                }
            } else { throw new Error('placeInside(): The doll passed must be smaller than this one.'); }
        } else { throw new Error('placeInside(): An instanceof RussianDoll must be passed.'); }
    }

    allDollsInsideMe () {
        if (this.inside) {
            const subInside = this.inside.allDollsInsideMe();
            let res = [];
            for (let doll of subInside) { res.push(doll); }
            res.unshift(this.inside);
            return res;
        } else {
            return '';
        }
    }

}

/**
 * 
 */