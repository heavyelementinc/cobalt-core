/**
 * @todo complete this.
 */
 class AudioPlayer extends HTMLElement {
    constructor() {
        super();
        // this.shadowRoot = true;
        this.setAttribute("__custom-input", "true");
    }

    connectedCallback() {
        this.innerHTML = `<audio preload="none">${this.innerHTML}If you can see this, you need a better web browser.</audio>${this.controls()}`;
        this.audio = this.querySelector("audio");
        this.button = this.querySelector("[name='playpause']");
        this.volume = this.querySelector("[name='volume']");
        this.duration = this.querySelector("[name='duration']");
        this.current = this.querySelector("[name='current']");
        this.seek = this.querySelector(".svg-waveform");
        this.progress = this.querySelector(".waveform rect");
        this.getWaveform();
        this.initListeners();
    }

    controls() {
        return `
            <div class="hbox">
                <button name="playpause"></ion-icon></button>
                <div class="waveform">
                <svg class="svg-waveform" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <rect class="playback" width="0%" height="100%"></rect>
                    <rect class="preload" width="0%" height="100%"></rect>
                    <svg y="0%" width="100%" height="100%"></svg>
                    </svg>
                </div>
            </div>
            <div class="hbox audio-player--underhang">
                <input type="range" name="volume" min="0" max="100">
                <div>
                    <span name="current">0:00</span> / <span name="duration">0:00</span>
                </div>
            </div>
            <!--<input type="range" name="progress" min="0" max="100" value="0">-->
        `;
    }

    initListeners() {
        this.audio.addEventListener('loadedmetadata', () => {
            this.duration.textContent = this.calculateTime(this.audio.duration);
        });
        this.audio.addEventListener("timeupdate", e => {
            this.progress.setAttribute("width", `${(this.audio.currentTime / this.audio.duration) * 100}%` );
            this.current.textContent = this.calculateTime(this.audio.currentTime);
        })
        this.audio.addEventListener("play", () => {
            this.classList.add("playing");
            // this.button.querySelector("[name='play']").style.display = "none";
            // this.button.querySelector("[name='pause']").style.display = "block";
            this.style.transition = "width 1s";
        });
        this.audio.addEventListener("pause", () => {
            this.classList.remove("playing");
            // this.button.querySelector("[name='play']").style.display = "block";
            // this.button.querySelector("[name='pause']").style.display = "none";
            this.style.transition = "";
        });
        this.audio.addEventListener("ended",() =>{
            this.classList.remove("playing");
            this.style.transition = "";
        })

        this.seek.addEventListener("click", (e) => {
            this.handleSeeking(e);
        });

        this.button.addEventListener('click', () => {
            const val = this.audio.paused;
            if(val) {
                this.audio.play();
            } else {
                this.audio.pause();
            }
        });

    }

    async getWaveform(){
        let src = null;
        for(const e of this.audio.childNodes) {
            if(!e.src) continue;
            if(e.getAttribute("src").includes(".mp3")) {
                src = e.getAttribute("src");
                break;
            }
        }
        const api = new ApiFetch(`/api/v1/waveform/?file=${src}`, "GET", {});
        let result = await api.send();
        
        this.querySelector(".waveform").innerHTML = result;
        this.progress = this.querySelector(".waveform rect");
        let nodes = this.querySelectorAll(".waveform line");
        console.log(nodes.length);
        this.style.setProperty("--waveform-nodes", nodes.length ?? 1);

        this.seek = this.querySelector(".svg-waveform");
        this.seek.addEventListener("click",e => this.handleSeeking(e));
    }

    handleSeeking(e) {
        const off = get_offset(this.querySelector(".waveform"));
        const normal = e.clientX - off.x;
        const percent = normal / off.w;
        const time = this.audio.duration * percent;
        this.audio.currentTime = time;
    }

    calculateTime(to_convert) {
        const minutes = Math.floor(to_convert / 60);
        const seconds = Math.floor(to_convert % 60);
        const returnedSeconds = seconds < 10 ? `0${seconds}` : `${seconds}`;
        return `${minutes}:${returnedSeconds}`;
    }
}

customElements.define("audio-player", AudioPlayer)
