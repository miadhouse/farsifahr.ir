/**
 * ============================================================================
 * HOKM ROYALE (حکم رویال) — Luxury Persian 4-Player Card Game Engine
 * Features:
 * - Scalable CSS Playing Cards (Abhijit Darji / Don Park architecture)
 * - Complete Persian Hokm Rules (تعیین حاکم با اولین آس، انتخاب حکم، الزام به خال، بریدن، کُت، کُت حاکم)
 * - Intelligent AI for Partner & Opponents
 * - Pure Web Audio API Sound Synthesizer (No external asset dependency)
 * - Cinematic Card Throw & Collection Animations
 * - Canvas Confetti Celebration Engine
 * - Full Mobile & Touch Responsiveness
 * ============================================================================
 */

(function () {
    'use strict';

    // ------------------------------------------------------------------------
    // 1. Audio Engine (Web Audio API Synthesizer)
    // ------------------------------------------------------------------------
    class SoundFX {
        constructor() {
            this.ctx = null;
            this.enabled = true;
            this.initOnGesture = this.initOnGesture.bind(this);
            window.addEventListener('click', this.initOnGesture, { once: true });
            window.addEventListener('touchstart', this.initOnGesture, { once: true });
        }

        initOnGesture() {
            if (!this.ctx) {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (AudioCtx) {
                    this.ctx = new AudioCtx();
                }
            }
            if (this.ctx && this.ctx.state === 'suspended') {
                this.ctx.resume();
            }
        }

        ensureContext() {
            if (!this.ctx) {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (AudioCtx) this.ctx = new AudioCtx();
            }
            if (this.ctx && this.ctx.state === 'suspended') {
                this.ctx.resume();
            }
            return this.ctx;
        }

        toggle() {
            this.enabled = !this.enabled;
            return this.enabled;
        }

        // Crisp card sliding swoosh
        cardSlide() {
            if (!this.enabled) return;
            const ctx = this.ensureContext();
            if (!ctx) return;

            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            const filter = ctx.createBiquadFilter();

            osc.type = 'triangle';
            osc.frequency.setValueAtTime(320, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(140, ctx.currentTime + 0.12);

            filter.type = 'lowpass';
            filter.frequency.setValueAtTime(1200, ctx.currentTime);

            gain.gain.setValueAtTime(0.08, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);

            osc.connect(filter);
            filter.connect(gain);
            gain.connect(ctx.destination);

            osc.start();
            osc.stop(ctx.currentTime + 0.13);
        }

        // Tactile snap/thud of card hitting velvet felt
        cardSnap() {
            if (!this.enabled) return;
            const ctx = this.ensureContext();
            if (!ctx) return;

            // Punchy click
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(260, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(50, ctx.currentTime + 0.08);

            gain.gain.setValueAtTime(0.25, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.09);

            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.09);

            // Velvet thud noise
            const bufferSize = ctx.sampleRate * 0.05;
            const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
            const data = buffer.getChannelData(0);
            for (let i = 0; i < bufferSize; i++) {
                data[i] = (Math.random() * 2 - 1) * Math.exp(-i / (bufferSize * 0.25));
            }
            const noise = ctx.createBufferSource();
            noise.buffer = buffer;
            const nGain = ctx.createGain();
            nGain.gain.setValueAtTime(0.12, ctx.currentTime);
            nGain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.05);
            noise.connect(nGain);
            nGain.connect(ctx.destination);
            noise.start();
        }

        // Collecting trick sweep sound
        trickCollect() {
            if (!this.enabled) return;
            const ctx = this.ensureContext();
            if (!ctx) return;

            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(180, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(420, ctx.currentTime + 0.18);

            gain.gain.setValueAtTime(0.08, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.2);

            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.21);
        }

        // Trump declaration royal fanfare
        trumpFanfare() {
            if (!this.enabled) return;
            const ctx = this.ensureContext();
            if (!ctx) return;

            const notes = [261.63, 329.63, 392.00, 523.25]; // C4, E4, G4, C5
            notes.forEach((freq, idx) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(freq, ctx.currentTime + idx * 0.08);

                gain.gain.setValueAtTime(0.12, ctx.currentTime + idx * 0.08);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + idx * 0.08 + 0.35);

                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(ctx.currentTime + idx * 0.08);
                osc.stop(ctx.currentTime + idx * 0.08 + 0.36);
            });
        }

        // Hand or Match win victory chime
        winFanfare() {
            if (!this.enabled) return;
            const ctx = this.ensureContext();
            if (!ctx) return;

            const notes = [392.00, 523.25, 659.25, 783.99, 1046.50]; // G4, C5, E5, G5, C6
            notes.forEach((freq, idx) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, ctx.currentTime + idx * 0.1);

                gain.gain.setValueAtTime(0.15, ctx.currentTime + idx * 0.1);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + idx * 0.1 + 0.5);

                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(ctx.currentTime + idx * 0.1);
                osc.stop(ctx.currentTime + idx * 0.1 + 0.52);
            });
        }

        // Soft chip click
        chipClick() {
            if (!this.enabled) return;
            const ctx = this.ensureContext();
            if (!ctx) return;

            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(1400, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(700, ctx.currentTime + 0.04);

            gain.gain.setValueAtTime(0.1, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.04);

            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.045);
        }
    }

    // ------------------------------------------------------------------------
    // 2. Confetti Particle System
    // ------------------------------------------------------------------------
    class Confetti {
        constructor(canvasId) {
            this.canvas = document.getElementById(canvasId);
            this.ctx = this.canvas ? this.canvas.getContext('2d') : null;
            this.particles = [];
            this.running = false;
            this.resize();
            window.addEventListener('resize', () => this.resize());
        }

        resize() {
            if (!this.canvas) return;
            this.canvas.width = window.innerWidth;
            this.canvas.height = window.innerHeight;
        }

        blast(count = 120) {
            if (!this.canvas || !this.ctx) return;
            this.resize();
            const colors = ['#dfb15b', '#f5d485', '#22c55e', '#ef4444', '#3b82f6', '#ec4899', '#ffffff'];
            for (let i = 0; i < count; i++) {
                this.particles.push({
                    x: this.canvas.width / 2 + (Math.random() - 0.5) * 120,
                    y: this.canvas.height / 2 + (Math.random() - 0.5) * 60,
                    vx: (Math.random() - 0.5) * 16,
                    vy: -Math.random() * 14 - 6,
                    size: Math.random() * 8 + 4,
                    color: colors[Math.floor(Math.random() * colors.length)],
                    rot: Math.random() * 360,
                    vrot: (Math.random() - 0.5) * 12,
                    gravity: 0.35,
                    opacity: 1
                });
            }
            if (!this.running) {
                this.running = true;
                this.animate();
            }
        }

        animate() {
            if (!this.running) return;
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

            for (let i = this.particles.length - 1; i >= 0; i--) {
                const p = this.particles[i];
                p.x += p.vx;
                p.y += p.vy;
                p.vy += p.gravity;
                p.rot += p.vrot;
                p.opacity -= 0.007;

                if (p.opacity <= 0 || p.y > this.canvas.height + 50) {
                    this.particles.splice(i, 1);
                    continue;
                }

                this.ctx.save();
                this.ctx.translate(p.x, p.y);
                this.ctx.rotate((p.rot * Math.PI) / 180);
                this.ctx.globalAlpha = Math.max(0, p.opacity);
                this.ctx.fillStyle = p.color;
                this.ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 1.4);
                this.ctx.restore();
            }

            if (this.particles.length > 0) {
                requestAnimationFrame(() => this.animate());
            } else {
                this.running = false;
                this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            }
        }
    }

    // ------------------------------------------------------------------------
    // 3. Card Definition & Codepen HTML Generator
    // ------------------------------------------------------------------------
    const SUITS = {
        S: { id: 'S', name: 'spade', symbol: '♠', fa: 'پیک', color: 'black' },
        H: { id: 'H', name: 'heart', symbol: '♥', fa: 'دل', color: 'red' },
        C: { id: 'C', name: 'club', symbol: '♣', fa: 'گشنیز', color: 'black' },
        D: { id: 'D', name: 'diamond', symbol: '♦', fa: 'خشت', color: 'red' }
    };

    const RANK_LABELS = {
        2: '2', 3: '3', 4: '4', 5: '5', 6: '6', 7: '7',
        8: '8', 9: '9', 10: '10', 11: 'J', 12: 'Q', 13: 'K', 14: 'A'
    };

    // Pip layouts for ranks 2..10 matching scalable CSS playing cards
    const PIP_LAYOUTS = {
        2: ['top_center', 'bottom_center'],
        3: ['top_center', 'middle_center', 'bottom_center'],
        4: ['top_left', 'top_right', 'bottom_left', 'bottom_right'],
        5: ['top_left', 'top_right', 'middle_center', 'bottom_left', 'bottom_right'],
        6: ['top_left', 'top_right', 'middle_left', 'middle_right', 'bottom_left', 'bottom_right'],
        7: ['top_left', 'top_right', 'middle_left', 'middle_right', 'bottom_left', 'bottom_right', 'middle_top'],
        8: ['top_left', 'top_right', 'middle_left', 'middle_right', 'bottom_left', 'bottom_right', 'middle_top', 'middle_bottom'],
        9: ['top_left', 'top_right', 'middle_top_left', 'middle_top_right', 'middle_bottom_left', 'middle_bottom_right', 'bottom_left', 'bottom_right', 'middle_center'],
        10: ['top_left', 'top_right', 'middle_top_left', 'middle_top_right', 'middle_bottom_left', 'middle_bottom_right', 'bottom_left', 'bottom_right', 'middle_top_center', 'middle_bottom_center']
    };

    class Card {
        constructor(rank, suitKey) {
            this.rank = rank; // 2..14
            this.suitKey = suitKey; // 'S', 'H', 'C', 'D'
            this.suit = SUITS[suitKey];
            this.label = RANK_LABELS[rank];
            this.id = `${this.suitKey}_${this.rank}`;
        }

        get isFace() {
            return this.rank >= 11 && this.rank <= 13;
        }

        get isAce() {
            return this.rank === 14;
        }

        get faceName() {
            if (this.rank === 11) return 'jack';
            if (this.rank === 12) return 'queen';
            if (this.rank === 13) return 'king';
            return '';
        }

        /**
         * Render card DOM strictly adhering to Codepen scalable CSS structure
         */
        renderDOM() {
            const cardEl = document.createElement('div');
            cardEl.className = `card ${this.suit.name}`;
            if (this.isAce) cardEl.classList.add('card-ace');
            cardEl.dataset.cardId = this.id;
            cardEl.dataset.suit = this.suitKey;
            cardEl.dataset.rank = this.rank;

            const inner = document.createElement('div');
            inner.className = 'card-inner';

            // Top corner
            const topCorner = document.createElement('div');
            topCorner.className = 'corner top';
            topCorner.innerHTML = `<span class="number">${this.label}</span><span>${this.suit.symbol}</span>`;
            inner.appendChild(topCorner);

            // Center content
            if (this.isFace) {
                const faceDiv = document.createElement('div');
                faceDiv.className = 'face';
                const img = document.createElement('img');
                img.src = `assets/img/faces/face-${this.faceName}-${this.suit.name}.png`;
                img.alt = `${this.label} ${this.suit.name}`;
                faceDiv.appendChild(img);
                inner.appendChild(faceDiv);
            } else if (this.isAce) {
                const megaPip = document.createElement('span');
                megaPip.className = 'suit middle_center';
                megaPip.textContent = this.suit.symbol;
                inner.appendChild(megaPip);
            } else {
                const pips = PIP_LAYOUTS[this.rank] || [];
                pips.forEach(posClass => {
                    const pip = document.createElement('span');
                    pip.className = `suit ${posClass}`;
                    pip.textContent = this.suit.symbol;
                    inner.appendChild(pip);
                });
            }

            // Bottom corner
            const bottomCorner = document.createElement('div');
            bottomCorner.className = 'corner bottom';
            bottomCorner.innerHTML = `<span class="number">${this.label}</span><span>${this.suit.symbol}</span>`;
            inner.appendChild(bottomCorner);

            cardEl.appendChild(inner);
            return cardEl;
        }

        static renderBack() {
            const cardEl = document.createElement('div');
            cardEl.className = 'card back';
            const pat = document.createElement('div');
            pat.className = 'card-back-pattern';
            cardEl.appendChild(pat);
            return cardEl;
        }
    }

    // ------------------------------------------------------------------------
    // 4. Deck Builder & Shuffler
    // ------------------------------------------------------------------------
    class Deck {
        constructor() {
            this.cards = [];
            this.reset();
        }

        reset() {
            this.cards = [];
            ['S', 'H', 'C', 'D'].forEach(s => {
                for (let r = 2; r <= 14; r++) {
                    this.cards.push(new Card(r, s));
                }
            });
        }

        shuffle() {
            for (let i = this.cards.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [this.cards[i], this.cards[j]] = [this.cards[j], this.cards[i]];
            }
        }

        draw() {
            return this.cards.pop();
        }
    }

    // ------------------------------------------------------------------------
    // 5. Hokm Game Engine
    // ------------------------------------------------------------------------
    class HokmGame {
        constructor() {
            this.sound = new SoundFX();
            this.confetti = new Confetti('confetti');
            this.deck = new Deck();

            // Players: 0 = You (bottom), 1 = Bahram (right), 2 = Sara (partner/top), 3 = Niloofar (left)
            this.players = [
                { id: 0, name: 'شما', isAI: false, team: 0, seat: 0, hand: [], tricks: 0 },
                { id: 1, name: 'استاد بهرام', isAI: true, team: 1, seat: 1, hand: [], tricks: 0 },
                { id: 2, name: 'سارا (یار شما)', isAI: true, team: 0, seat: 2, hand: [], tricks: 0 },
                { id: 3, name: 'نیلوفر', isAI: true, team: 1, seat: 3, hand: [], tricks: 0 }
            ];

            // Match score (sets of hands won, first team to 7)
            this.score = { us: 0, them: 0 };
            this.tricks = { us: 0, them: 0 };

            this.hakem = null; // Index of player who is Hakem
            this.trumpSuit = null; // S, H, C, D
            this.currentTrick = []; // Array of { playerIndex, card, element }
            this.leadSuit = null;
            this.turnIndex = 0; // Current player turn
            this.state = 'INITIAL'; // INITIAL, DETERMINING_HAKEM, SELECTING_TRUMP, PLAYING, ROUND_OVER, MATCH_OVER
            this.isAnimatingPlay = false;

            // Floating dialogue pool for AI players
            this.dialogues = {
                partnerWin: ['دمت گرم یار من!', 'ایول، چه کارتی!', 'دست برای ما شد.', 'عالی بود!'],
                partnerCut: ['من بریدم!', 'حکم دست منه!', 'نگران نباش، جمعش کردم.'],
                oppCut: ['این دست که پرید!', 'حکم آوردم وسط!', 'بریدیم دست رو!'],
                oppWin: ['استاد کار خودشو بلده.', 'دست بعدی هم مال ماست!', 'امتیاز خوبی شد.']
            };

            this.bindDOM();
            this.setupFloatingDust();
        }

        bindDOM() {
            this.stage = document.getElementById('stage');
            this.tableZone = document.getElementById('tableZone');
            this.scoreUsEl = document.getElementById('scoreUs');
            this.scoreThemEl = document.getElementById('scoreThem');
            this.tricksUsEl = document.getElementById('tricksUs');
            this.tricksThemEl = document.getElementById('tricksThem');
            this.trumpSuitEl = document.getElementById('trumpSuit');
            this.announceEl = document.getElementById('announce');
            this.toastEl = document.getElementById('toast');

            // Modals
            this.startModal = document.getElementById('startModal');
            this.trumpModal = document.getElementById('trumpModal');
            this.resultModal = document.getElementById('resultModal');
            this.rulesModal = document.getElementById('rulesModal');

            // Controls
            document.getElementById('btnStart').addEventListener('click', () => {
                const nameInput = document.getElementById('playerName');
                const pName = nameInput.value.trim();
                if (pName) {
                    this.players[0].name = pName;
                    const p0name = document.querySelector('#seat0 .pname');
                    if (p0name) p0name.textContent = pName;
                }
                this.sound.chipClick();
                this.startModal.classList.remove('show');
                this.startNewMatch();
            });

            document.getElementById('btnSound').addEventListener('click', (e) => {
                const active = this.sound.toggle();
                e.currentTarget.textContent = active ? '🔊' : '🔇';
                e.currentTarget.title = active ? 'صدا فعال' : 'صدا قطع';
            });

            document.getElementById('btnRules').addEventListener('click', () => {
                this.sound.chipClick();
                this.rulesModal.classList.add('show');
            });

            document.querySelector('[data-close="rulesModal"]').addEventListener('click', () => {
                this.sound.chipClick();
                this.rulesModal.classList.remove('show');
            });

            document.getElementById('btnRestart').addEventListener('click', () => {
                if (confirm('آیا مایلید بازی جدیدی آغاز کنید؟')) {
                    this.sound.chipClick();
                    this.startNewMatch();
                }
            });

            document.getElementById('btnNext').addEventListener('click', () => {
                this.sound.chipClick();
                this.resultModal.classList.remove('show');
                if (this.state === 'MATCH_OVER') {
                    this.startNewMatch();
                } else {
                    this.startHand();
                }
            });

            // Trump choice buttons
            document.querySelectorAll('.trump-choices button').forEach(btn => {
                btn.addEventListener('click', () => {
                    const suit = btn.dataset.suit;
                    this.sound.trumpFanfare();
                    this.trumpModal.classList.remove('show');
                    this.onTrumpChosen(suit);
                });
            });

            window.addEventListener('resize', () => {
                if (this.state === 'PLAYING' || this.state === 'SELECTING_TRUMP') {
                    this.renderPlayerHand();
                    this.renderOpponentHands();
                    this.repositionCurrentTrick();
                }
            });
        }

        // Atmosphere: gentle floating dust motes
        setupFloatingDust() {
            const dust = document.getElementById('dust');
            if (!dust) return;
            for (let i = 0; i < 28; i++) {
                const mote = document.createElement('div');
                mote.style.position = 'absolute';
                mote.style.width = `${Math.random() * 3 + 1}px`;
                mote.style.height = mote.style.width;
                mote.style.borderRadius = '50%';
                mote.style.background = 'rgba(255, 230, 150, 0.4)';
                mote.style.top = `${Math.random() * 90}%`;
                mote.style.left = `${Math.random() * 95}%`;
                mote.style.filter = 'blur(1px)';
                mote.style.opacity = Math.random() * 0.6 + 0.2;
                mote.style.animation = `dustFloat ${Math.random() * 8 + 6}s infinite ease-in-out alternate`;
                dust.appendChild(mote);
            }
        }

        showAnnounce(text, duration = 2200) {
            this.announceEl.textContent = text;
            this.announceEl.classList.add('show');
            if (this._announceTimer) clearTimeout(this._announceTimer);
            this._announceTimer = setTimeout(() => {
                this.announceEl.classList.remove('show');
            }, duration);
        }

        showToast(text, duration = 2000) {
            this.toastEl.textContent = text;
            this.toastEl.classList.add('show');
            if (this._toastTimer) clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => {
                this.toastEl.classList.remove('show');
            }, duration);
        }

        showBubble(playerIndex, text, duration = 2400) {
            const seat = document.getElementById(`seat${playerIndex}`);
            if (!seat) return;
            const bubble = seat.querySelector('.bubble');
            if (!bubble) return;
            bubble.textContent = text;
            bubble.classList.add('show');
            setTimeout(() => {
                bubble.classList.remove('show');
            }, duration);
        }

        updateHUD() {
            this.scoreUsEl.textContent = this.score.us;
            this.scoreThemEl.textContent = this.score.them;
            this.tricksUsEl.textContent = `${this.tricks.us} دست`;
            this.tricksThemEl.textContent = `${this.tricks.them} دست`;

            // Player tricks under avatar
            this.players.forEach(p => {
                const seat = document.getElementById(`seat${p.seat}`);
                if (seat) {
                    const tc = seat.querySelector('.tcount');
                    if (tc) tc.textContent = p.tricks;
                    if (this.hakem === p.id) {
                        seat.classList.add('is-hakem');
                    } else {
                        seat.classList.remove('is-hakem');
                    }
                }
            });

            // Trump display
            if (this.trumpSuit) {
                const s = SUITS[this.trumpSuit];
                this.trumpSuitEl.textContent = s.symbol;
                this.trumpSuitEl.className = `trump-suit ${s.color}`;
            } else {
                this.trumpSuitEl.textContent = '—';
                this.trumpSuitEl.className = 'trump-suit';
            }
        }

        setActiveTurn(playerIndex) {
            document.querySelectorAll('.seat').forEach((seat, idx) => {
                if (idx === playerIndex) {
                    seat.classList.add('active-turn');
                } else {
                    seat.classList.remove('active-turn');
                }
            });
        }

        clearActiveTurn() {
            document.querySelectorAll('.seat').forEach(seat => seat.classList.remove('active-turn'));
        }

        // --------------------------------------------------------------------
        // Start New Match
        // --------------------------------------------------------------------
        startNewMatch() {
            this.score.us = 0;
            this.score.them = 0;
            this.hakem = null;
            this.stage.innerHTML = '';
            this.updateHUD();
            this.determineHakem();
        }

        // --------------------------------------------------------------------
        // Step 1: Determine Hakem by Dealing Cards until First Ace
        // --------------------------------------------------------------------
        async determineHakem() {
            this.state = 'DETERMINING_HAKEM';
            this.deck.reset();
            this.deck.shuffle();
            this.stage.innerHTML = '';
            this.showAnnounce('تعیین حاکم: اولین آس حاکم می‌شود', 2600);
            await this.sleep(1200);

            let currentCandidate = 0; // Starts from player 0
            let hakemFound = null;

            while (hakemFound === null && this.deck.cards.length > 0) {
                this.setActiveTurn(currentCandidate);
                const card = this.deck.draw();
                const cardEl = card.renderDOM();
                cardEl.classList.add('card-determining');

                // Animate to position just below/above player seat
                this.stage.appendChild(cardEl);
                this.sound.cardSlide();

                const seatRect = document.getElementById(`seat${currentCandidate}`).getBoundingClientRect();
                const stageRect = this.stage.getBoundingClientRect();

                // Target slightly offset toward table center
                let tx = seatRect.left + seatRect.width / 2 - stageRect.left - (cardEl.offsetWidth / 2);
                let ty = seatRect.top + seatRect.height / 2 - stageRect.top - (cardEl.offsetHeight / 2);

                if (currentCandidate === 0) ty -= 85;
                if (currentCandidate === 1) tx -= 85;
                if (currentCandidate === 2) ty += 85;
                if (currentCandidate === 3) tx += 85;

                cardEl.style.left = `${stageRect.width / 2 - cardEl.offsetWidth / 2}px`;
                cardEl.style.top = `${stageRect.height / 2 - cardEl.offsetHeight / 2}px`;
                cardEl.style.transform = 'scale(0.3)';
                cardEl.style.opacity = '0';
                cardEl.style.transition = 'all 0.35s cubic-bezier(0.2, 0.8, 0.3, 1)';

                // Force layout
                cardEl.offsetHeight;

                cardEl.style.left = `${tx}px`;
                cardEl.style.top = `${ty}px`;
                cardEl.style.transform = 'scale(1) rotate(0deg)';
                cardEl.style.opacity = '1';

                this.sound.cardSnap();

                if (card.isAce) {
                    hakemFound = currentCandidate;
                    cardEl.style.boxShadow = '0 0 25px 8px #f5d485';
                    cardEl.style.transform = 'scale(1.15)';
                    this.sound.winFanfare();
                    break;
                }

                await this.sleep(400);
                // Advance clockwise
                currentCandidate = (currentCandidate + 1) % 4;
            }

            this.clearActiveTurn();
            this.hakem = hakemFound;
            const hakemPlayer = this.players[this.hakem];
            this.updateHUD();

            this.showAnnounce(`👑 ${hakemPlayer.name} حاکم شد!`, 2500);
            this.showBubble(this.hakem, 'من حاکم شدم!', 2200);

            await this.sleep(2200);

            // Clean up determining cards
            const allD = document.querySelectorAll('.card-determining');
            allD.forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'scale(0.5)';
            });
            await this.sleep(400);
            allD.forEach(el => el.remove());

            this.startHand();
        }

        // --------------------------------------------------------------------
        // Step 2: Start Hand (Deal 5 cards to each, pick Trump)
        // --------------------------------------------------------------------
        async startHand() {
            this.state = 'SELECTING_TRUMP';
            this.trumpSuit = null;
            this.currentTrick = [];
            this.leadSuit = null;
            this.tricks.us = 0;
            this.tricks.them = 0;
            this.players.forEach(p => {
                p.hand = [];
                p.tricks = 0;
            });
            this.updateHUD();
            this.stage.innerHTML = '';

            this.deck.reset();
            this.deck.shuffle();

            this.showAnnounce('دست اول کارت‌ها (۵ کارت)...', 1400);
            await this.sleep(600);

            // Deal first 5 cards to each player starting with Hakem
            for (let round = 0; round < 5; round++) {
                for (let i = 0; i < 4; i++) {
                    const pIndex = (this.hakem + i) % 4;
                    this.players[pIndex].hand.push(this.deck.draw());
                }
                this.sound.cardSlide();
                await this.sleep(80);
            }

            // Sort player 0's 5 cards
            this.sortHand(0);
            this.renderPlayerHand();
            this.renderOpponentHands();

            await this.sleep(500);

            // Hakem chooses trump
            if (this.hakem === 0) {
                // User is Hakem
                this.trumpModal.classList.add('show');
            } else {
                // AI is Hakem
                this.setActiveTurn(this.hakem);
                await this.sleep(1200);
                const bestSuit = this.aiChooseTrump(this.hakem);
                this.onTrumpChosen(bestSuit);
            }
        }

        aiChooseTrump(playerIndex) {
            const hand = this.players[playerIndex].hand;
            // Count cards and power per suit
            const suitScores = { S: 0, H: 0, C: 0, D: 0 };
            hand.forEach(card => {
                // Weight count + high card values
                suitScores[card.suitKey] += 10 + card.rank;
            });

            let bestSuit = 'S';
            let maxScore = -1;
            Object.entries(suitScores).forEach(([suit, score]) => {
                if (score > maxScore) {
                    maxScore = score;
                    bestSuit = suit;
                }
            });
            return bestSuit;
        }

        async onTrumpChosen(suitKey) {
            this.trumpSuit = suitKey;
            const suitInfo = SUITS[suitKey];
            this.sound.trumpFanfare();
            this.updateHUD();

            const hakemName = this.players[this.hakem].name;
            this.showAnnounce(`حکم: ${suitInfo.symbol} ${suitInfo.fa} (توسط ${hakemName})`, 2500);
            this.showBubble(this.hakem, `حکم: ${suitInfo.fa} ${suitInfo.symbol}`, 2200);

            await this.sleep(1400);

            // Deal remaining 8 cards (to make 13 cards total each)
            this.showAnnounce('پخش بقیه کارت‌ها...', 1200);
            for (let round = 0; round < 8; round++) {
                for (let i = 0; i < 4; i++) {
                    const pIndex = (this.hakem + i) % 4;
                    this.players[pIndex].hand.push(this.deck.draw());
                }
                this.sound.cardSlide();
                await this.sleep(60);
            }

            // Sort hands
            this.players.forEach((p, idx) => this.sortHand(idx));
            this.renderPlayerHand();
            this.renderOpponentHands();

            await this.sleep(800);

            // First trick starts with Hakem
            this.state = 'PLAYING';
            this.turnIndex = this.hakem;
            this.startTurn();
        }

        sortHand(playerIndex) {
            // Sort order: suits grouped (S, H, C, D), within suit descending rank
            const suitOrder = { S: 0, H: 1, C: 2, D: 3 };
            this.players[playerIndex].hand.sort((a, b) => {
                if (a.suitKey !== b.suitKey) {
                    return suitOrder[a.suitKey] - suitOrder[b.suitKey];
                }
                return b.rank - a.rank;
            });
        }

        // --------------------------------------------------------------------
        // Rendering Hands (Player 0 Arc Fan & AI Seat Stacks)
        // --------------------------------------------------------------------
        renderPlayerHand() {
            // Remove previous in-hand player cards
            const oldHandCards = document.querySelectorAll('.card.player-hand');
            oldHandCards.forEach(c => c.remove());

            const player = this.players[0];
            const count = player.hand.length;
            if (count === 0) return;

            const stageRect = this.stage.getBoundingClientRect();
            const centerX = stageRect.width / 2;
            const bottomY = stageRect.height - 18;

            // Compute spacing based on screen width
            const isMobile = stageRect.width < 600;
            const cardWidth = isMobile ? 68 : (stageRect.width < 900 ? 80 : 96);
            const maxSpread = Math.min(stageRect.width * 0.88, count * (cardWidth * 0.42) + cardWidth);
            const stepX = count > 1 ? maxSpread / (count - 1) : 0;
            const startX = centerX - maxSpread / 2;

            player.hand.forEach((card, i) => {
                const cardEl = card.renderDOM();
                cardEl.classList.add('in-hand', 'player-hand');
                cardEl.dataset.index = i;

                const centerOffset = i - (count - 1) / 2;
                const posX = startX + i * stepX - cardWidth / 2;
                // Parabolic vertical curve
                const posY = bottomY - 110 + Math.abs(centerOffset) * (isMobile ? 3 : 5);
                const rot = centerOffset * (isMobile ? 2.2 : 3);

                cardEl.style.left = `${posX}px`;
                cardEl.style.top = `${posY}px`;
                cardEl.style.transform = `rotate(${rot}deg)`;
                cardEl.style.zIndex = 10 + i;

                // Playable validation
                if (this.state === 'PLAYING' && this.turnIndex === 0) {
                    const canPlay = this.isValidMove(0, card);
                    if (canPlay) {
                        cardEl.classList.add('playable');
                    } else {
                        cardEl.classList.add('unplayable');
                    }
                }

                cardEl.addEventListener('click', () => {
                    if (this.state !== 'PLAYING' || this.turnIndex !== 0 || this.isAnimatingPlay) return;
                    if (!this.isValidMove(0, card)) {
                        this.showToast('پیروی از خال زمین الزامی است!');
                        return;
                    }
                    this.playCard(0, card, cardEl);
                });

                this.stage.appendChild(cardEl);
            });
        }

        renderOpponentHands() {
            // Render neat compact mini-stacks of card backs near AI seats to show card count
            const oldBacks = document.querySelectorAll('.card.ai-hand-back');
            oldBacks.forEach(b => b.remove());

            [1, 2, 3].forEach(seatIdx => {
                const p = this.players[seatIdx];
                const count = p.hand.length;
                if (count === 0) return;

                const seat = document.getElementById(`seat${seatIdx}`);
                if (!seat) return;
                const sRect = seat.getBoundingClientRect();
                const stageRect = this.stage.getBoundingClientRect();

                // Generate a subtle overlapping back fan
                const maxShown = Math.min(count, 7); // Show up to 7 layered backs
                for (let k = 0; k < maxShown; k++) {
                    const back = Card.renderBack();
                    back.classList.add('ai-hand-back');
                    back.style.pointerEvents = 'none';
                    back.style.transform = 'scale(0.38)';
                    back.style.transformOrigin = 'center center';
                    back.style.opacity = '0.9';
                    back.style.boxShadow = '0 2px 8px rgba(0,0,0,0.6)';

                    let bx = 0, by = 0, brot = 0;

                    if (seatIdx === 1) { // Right opponent
                        bx = sRect.left - stageRect.left - 50 + (k * 2);
                        by = sRect.top - stageRect.top + (k * 3) - 10;
                        brot = 90 + (k - maxShown / 2) * 3;
                    } else if (seatIdx === 2) { // Top partner
                        bx = sRect.left - stageRect.left + (k * 6) - 15;
                        by = sRect.top - stageRect.top + 75;
                        brot = (k - maxShown / 2) * 4;
                    } else if (seatIdx === 3) { // Left opponent
                        bx = sRect.right - stageRect.left + 15 + (k * 2);
                        by = sRect.top - stageRect.top + (k * 3) - 10;
                        brot = -90 + (k - maxShown / 2) * 3;
                    }

                    back.style.left = `${bx}px`;
                    back.style.top = `${by}px`;
                    back.style.transform = `scale(0.38) rotate(${brot}deg)`;
                    back.style.zIndex = 4 + k;

                    this.stage.appendChild(back);
                }
            });
        }

        // --------------------------------------------------------------------
        // Step 3: Turn & Play Rules (Follow Suit, Cut, AI Decision)
        // --------------------------------------------------------------------
        isValidMove(playerIndex, card) {
            // If leading the trick, any card is valid
            if (this.currentTrick.length === 0 || !this.leadSuit) {
                return true;
            }

            const hand = this.players[playerIndex].hand;
            const hasLeadSuit = hand.some(c => c.suitKey === this.leadSuit);

            // Follow suit rule (پیروی از خال زمینه)
            if (hasLeadSuit) {
                return card.suitKey === this.leadSuit;
            }

            // If player doesn't have lead suit, ANY card is valid (cut with trump or discard)
            return true;
        }

        startTurn() {
            if (this.state !== 'PLAYING') return;

            this.setActiveTurn(this.turnIndex);
            this.renderPlayerHand();

            if (this.turnIndex === 0) {
                // Human turn: wait for click
                return;
            }

            // AI turn
            const aiPlayer = this.players[this.turnIndex];
            const thinkDelay = Math.floor(Math.random() * 400) + 600;

            setTimeout(() => {
                if (this.state !== 'PLAYING') return;
                const chosenCard = this.aiChooseCard(this.turnIndex);
                this.playCard(this.turnIndex, chosenCard);
            }, thinkDelay);
        }

        aiChooseCard(playerIndex) {
            const player = this.players[playerIndex];
            const hand = player.hand;
            const partnerIndex = (playerIndex + 2) % 4;

            // Valid playable cards
            const validCards = hand.filter(c => this.isValidMove(playerIndex, c));
            if (validCards.length === 1) return validCards[0];

            // 1. If AI is LEADING the trick:
            if (this.currentTrick.length === 0 || !this.leadSuit) {
                // Strategy: Lead non-trump Ace if held (secure tricks)
                const nonTrumpAces = validCards.filter(c => c.rank === 14 && c.suitKey !== this.trumpSuit);
                if (nonTrumpAces.length > 0) return nonTrumpAces[0];

                // Lead Ace of trump if held
                const trumpAce = validCards.find(c => c.rank === 14 && c.suitKey === this.trumpSuit);
                if (trumpAce && Math.random() < 0.6) return trumpAce;

                // Lead high card of longest suit
                const suitCounts = {};
                validCards.forEach(c => {
                    suitCounts[c.suitKey] = (suitCounts[c.suitKey] || 0) + 1;
                });
                let longestSuit = validCards[0].suitKey;
                let maxLen = 0;
                Object.entries(suitCounts).forEach(([s, cnt]) => {
                    if (s !== this.trumpSuit && cnt > maxLen) {
                        maxLen = cnt;
                        longestSuit = s;
                    }
                });
                const suitCards = validCards.filter(c => c.suitKey === longestSuit);
                // Play highest or lowest depending on cards held
                suitCards.sort((a, b) => b.rank - a.rank);
                return suitCards[0];
            }

            // 2. If AI is FOLLOWING the trick:
            const leadSuitCards = validCards.filter(c => c.suitKey === this.leadSuit);
            const currentWinner = this.evaluateCurrentWinner();
            const partnerIsWinning = currentWinner && currentWinner.playerIndex === partnerIndex;

            // Case A: AI HAS the lead suit
            if (leadSuitCards.length > 0) {
                leadSuitCards.sort((a, b) => a.rank - b.rank); // Ascending: [lowest ... highest]
                const highestLeadCard = leadSuitCards[leadSuitCards.length - 1];
                const lowestLeadCard = leadSuitCards[0];

                if (partnerIsWinning) {
                    // Partner is winning: play lowest card (خشت دادن)
                    return lowestLeadCard;
                } else {
                    // Opponent winning: try to beat current highest card of lead suit (if no trump played yet)
                    const trumpPlayed = this.currentTrick.some(t => t.card.suitKey === this.trumpSuit);
                    if (!trumpPlayed) {
                        const currentHighestRank = Math.max(...this.currentTrick.filter(t => t.card.suitKey === this.leadSuit).map(t => t.card.rank));
                        const winningCards = leadSuitCards.filter(c => c.rank > currentHighestRank);
                        if (winningCards.length > 0) {
                            // Play lowest card that still beats the opponent
                            return winningCards[0];
                        }
                    }
                    // Cannot win: discard lowest
                    return lowestLeadCard;
                }
            }

            // Case B: AI is VOID of lead suit (نداشتن خال زمینه)
            const trumps = validCards.filter(c => c.suitKey === this.trumpSuit);
            trumps.sort((a, b) => a.rank - b.rank);

            if (partnerIsWinning) {
                // Partner winning: do not cut! Discard lowest non-trump card
                const nonTrumps = validCards.filter(c => c.suitKey !== this.trumpSuit);
                if (nonTrumps.length > 0) {
                    nonTrumps.sort((a, b) => a.rank - b.rank);
                    return nonTrumps[0];
                }
                return trumps[0];
            } else {
                // Opponents winning: try to cut (بریدن) with lowest possible trump
                const currentHighestTrump = this.currentTrick
                    .filter(t => t.card.suitKey === this.trumpSuit)
                    .reduce((max, t) => Math.max(max, t.card.rank), 0);

                const beatingTrumps = trumps.filter(t => t.rank > currentHighestTrump);
                if (beatingTrumps.length > 0) {
                    // Cut with lowest trump that wins!
                    return beatingTrumps[0];
                }

                // Cannot cut or trumps too low: discard weakest trash non-trump card
                const trashCards = validCards.filter(c => c.suitKey !== this.trumpSuit);
                if (trashCards.length > 0) {
                    trashCards.sort((a, b) => a.rank - b.rank);
                    return trashCards[0];
                }
                return validCards[0];
            }
        }

        evaluateCurrentWinner() {
            if (this.currentTrick.length === 0) return null;
            let winner = this.currentTrick[0];
            for (let i = 1; i < this.currentTrick.length; i++) {
                const cand = this.currentTrick[i];
                if (cand.card.suitKey === this.trumpSuit && winner.card.suitKey !== this.trumpSuit) {
                    winner = cand;
                } else if (cand.card.suitKey === winner.card.suitKey && cand.card.rank > winner.card.rank) {
                    winner = cand;
                }
            }
            return winner;
        }

        // --------------------------------------------------------------------
        // Step 4: Play Card Animation & Throw Snap
        // --------------------------------------------------------------------
        async playCard(playerIndex, card, existingEl = null) {
            if (this.isAnimatingPlay) return;
            this.isAnimatingPlay = true;

            const player = this.players[playerIndex];
            const handIndex = player.hand.findIndex(c => c.id === card.id);
            if (handIndex !== -1) {
                player.hand.splice(handIndex, 1);
            }

            if (this.currentTrick.length === 0) {
                this.leadSuit = card.suitKey;
            }

            // Create or reuse card DOM
            let cardEl = existingEl;
            if (!cardEl) {
                cardEl = card.renderDOM();
                this.stage.appendChild(cardEl);
            } else {
                cardEl.classList.remove('in-hand', 'player-hand', 'playable', 'unplayable');
            }

            cardEl.classList.add('trick-card');
            cardEl.style.pointerEvents = 'none';

            // Coordinates calculation relative to stage
            const stageRect = this.stage.getBoundingClientRect();
            const tableEl = document.getElementById('table') || this.tableZone;
            const tRect = tableEl.getBoundingClientRect();

            // Dimensions of card
            const cardW = cardEl.offsetWidth || 80;
            const cardH = cardEl.offsetHeight || 112;

            // Start position (from seat or clicked hand card)
            let startX, startY, startRot = 0;
            if (existingEl) {
                const curRect = existingEl.getBoundingClientRect();
                startX = curRect.left - stageRect.left;
                startY = curRect.top - stageRect.top;
                startRot = 0;
            } else {
                const seat = document.getElementById(`seat${playerIndex}`);
                const sRect = seat ? seat.getBoundingClientRect() : tRect;
                startX = sRect.left + sRect.width / 2 - stageRect.left - cardW / 2;
                startY = sRect.top + sRect.height / 2 - stageRect.top - cardH / 2;
                if (playerIndex === 1) startRot = 90;
                else if (playerIndex === 2) startRot = 180;
                else if (playerIndex === 3) startRot = -90;
            }

            // Target in center of felt table
            const tableCenterX = tRect.left + tRect.width / 2 - stageRect.left;
            const tableCenterY = tRect.top + tRect.height / 2 - stageRect.top;
            const centerX = tableCenterX - cardW / 2;
            const centerY = tableCenterY - cardH / 2;

            let endX = centerX;
            let endY = centerY;
            let endRot = 0;

            if (playerIndex === 0) {
                endY += 36;
                endRot = (Math.random() - 0.5) * 6;
            } else if (playerIndex === 1) {
                endX += 44;
                endRot = 86 + (Math.random() - 0.5) * 8;
            } else if (playerIndex === 2) {
                endY -= 36;
                endRot = 180 + (Math.random() - 0.5) * 6;
            } else if (playerIndex === 3) {
                endX -= 44;
                endRot = -86 + (Math.random() - 0.5) * 8;
            }

            // 1. Position card at start position immediately
            cardEl.style.position = 'absolute';
            cardEl.style.left = `${startX}px`;
            cardEl.style.top = `${startY}px`;
            cardEl.style.right = 'auto';
            cardEl.style.bottom = 'auto';
            cardEl.style.transform = `scale(0.9) rotate(${startRot}deg)`;
            cardEl.style.opacity = '1';
            cardEl.style.transition = 'none';
            cardEl.style.zIndex = `${30 + this.currentTrick.length}`;

            // Force reflow
            cardEl.offsetHeight;

            // 2. Animate to center of table
            this.sound.cardSlide();
            cardEl.style.transition = 'left 0.38s cubic-bezier(0.2, 0.9, 0.3, 1), top 0.38s cubic-bezier(0.2, 0.9, 0.3, 1), transform 0.38s cubic-bezier(0.2, 0.9, 0.3, 1), box-shadow 0.38s ease';
            cardEl.style.left = `${endX}px`;
            cardEl.style.top = `${endY}px`;
            cardEl.style.transform = `scale(1) rotate(${endRot}deg)`;
            cardEl.style.boxShadow = '0 10px 25px rgba(0,0,0,0.6)';

            setTimeout(() => this.sound.cardSnap(), 180);

            // Register played card in current trick
            this.currentTrick.push({
                playerIndex,
                card,
                element: cardEl,
                endX,
                endY,
                endRot
            });

            // Re-render hands
            if (playerIndex === 0) {
                this.renderPlayerHand();
            } else {
                this.renderOpponentHands();
            }

            // Fun dialogue bubble on cuts
            if (card.suitKey === this.trumpSuit && this.leadSuit !== this.trumpSuit) {
                const phrases = player.team === 0 ? this.dialogues.partnerCut : this.dialogues.oppCut;
                const txt = phrases[Math.floor(Math.random() * phrases.length)];
                this.showBubble(playerIndex, txt, 1800);
            }

            await this.sleep(450);
            this.isAnimatingPlay = false;

            // Advance turn or resolve trick
            if (this.currentTrick.length < 4) {
                this.turnIndex = (this.turnIndex + 1) % 4;
                this.startTurn();
            } else {
                this.clearActiveTurn();
                await this.sleep(600);
                this.resolveTrick();
            }
        }

        repositionCurrentTrick() {
            if (this.currentTrick.length === 0) return;
            const stageRect = this.stage.getBoundingClientRect();
            const tableEl = document.getElementById('table') || this.tableZone;
            const tRect = tableEl.getBoundingClientRect();
            const tableCenterX = tRect.left + tRect.width / 2 - stageRect.left;
            const tableCenterY = tRect.top + tRect.height / 2 - stageRect.top;

            this.currentTrick.forEach(item => {
                const cardW = item.element.offsetWidth || 80;
                const cardH = item.element.offsetHeight || 112;
                let endX = tableCenterX - cardW / 2;
                let endY = tableCenterY - cardH / 2;
                if (item.playerIndex === 0) endY += 36;
                else if (item.playerIndex === 1) endX += 44;
                else if (item.playerIndex === 2) endY -= 36;
                else if (item.playerIndex === 3) endX -= 44;
                item.endX = endX;
                item.endY = endY;
                item.element.style.transition = 'none';
                item.element.style.left = `${endX}px`;
                item.element.style.top = `${endY}px`;
            });
        }

        // --------------------------------------------------------------------
        // Step 5: Resolve Trick (Winner Determination & Trick Sweep)
        // --------------------------------------------------------------------
        async resolveTrick() {
            this.state = 'TRICK_RESOLVE';
            const winnerItem = this.evaluateCurrentWinner();
            const winnerPlayer = this.players[winnerItem.playerIndex];

            // Highlight winning card in the center table!
            winnerItem.element.classList.add('winning-trick');

            // Increment tricks count
            winnerPlayer.tricks += 1;
            if (winnerPlayer.team === 0) {
                this.tricks.us += 1;
            } else {
                this.tricks.them += 1;
            }
            this.updateHUD();

            // Dialogue / toast
            if (winnerPlayer.team === 0) {
                const phrase = this.dialogues.partnerWin[Math.floor(Math.random() * this.dialogues.partnerWin.length)];
                this.showBubble(2, phrase, 1800);
            } else {
                const phrase = this.dialogues.oppWin[Math.floor(Math.random() * this.dialogues.oppWin.length)];
                this.showBubble(1, phrase, 1800);
            }

            // Keep cards in center visible for 1.1s so players clearly see who won
            await this.sleep(1100);

            // Collect trick animation toward winner's seat
            const winnerSeat = document.getElementById(`seat${winnerItem.playerIndex}`);
            const wRect = winnerSeat.getBoundingClientRect();
            const stageRect = this.stage.getBoundingClientRect();

            const targetX = wRect.left + wRect.width / 2 - stageRect.left - winnerItem.element.offsetWidth / 2;
            const targetY = wRect.top + wRect.height / 2 - stageRect.top - winnerItem.element.offsetHeight / 2;

            this.sound.trickCollect();

            this.currentTrick.forEach(item => {
                item.element.classList.remove('winning-trick');
                item.element.style.transition = 'left 0.48s cubic-bezier(0.5, 0, 0.2, 1), top 0.48s cubic-bezier(0.5, 0, 0.2, 1), transform 0.48s cubic-bezier(0.5, 0, 0.2, 1), opacity 0.48s ease';
                item.element.style.left = `${targetX}px`;
                item.element.style.top = `${targetY}px`;
                item.element.style.transform = 'scale(0.3) rotate(0deg)';
                item.element.style.opacity = '0';
            });

            await this.sleep(520);

            // Clean up trick elements
            this.currentTrick.forEach(item => item.element.remove());
            this.currentTrick = [];
            this.leadSuit = null;

            // Check if hand is won (first team to 7 tricks)
            if (this.tricks.us >= 7 || this.tricks.them >= 7) {
                this.resolveHand();
            } else {
                // Next trick led by winner of current trick
                this.state = 'PLAYING';
                this.turnIndex = winnerItem.playerIndex;
                this.startTurn();
            }
        }

        // --------------------------------------------------------------------
        // Step 6: Hand End (Scoring: Normal 1pt, Kot 2pts, Hakem-Kot 3pts)
        // --------------------------------------------------------------------
        async resolveHand() {
            this.state = 'HAND_OVER';
            const weWon = this.tricks.us >= 7;
            const hakemTeam = this.players[this.hakem].team;
            const losingTricks = weWon ? this.tricks.them : this.tricks.us;

            let points = 1;
            let resultType = 'normal';

            // Check Kot rules
            if (losingTricks === 0) {
                // Team got 0 tricks!
                if ((weWon && hakemTeam === 1) || (!weWon && hakemTeam === 0)) {
                    // The Hakem's team got Kot (Hakem-Kot = 3 points!)
                    points = 3;
                    resultType = 'hakem_kot';
                } else {
                    // Regular Kot = 2 points
                    points = 2;
                    resultType = 'kot';
                }
            }

            // Award points
            if (weWon) {
                this.score.us += points;
                this.sound.winFanfare();
            } else {
                this.score.them += points;
                this.sound.cardSnap();
            }
            this.updateHUD();

            // Hakem rotation:
            // If Hakem's team won, Hakem remains unchanged.
            // If Hakem's team lost, Hakem rotates to next player clockwise.
            const hakemWon = (hakemTeam === 0 && weWon) || (hakemTeam === 1 && !weWon);
            if (!hakemWon) {
                this.hakem = (this.hakem + 1) % 4;
            }

            // Check for Match Over (first to 7 match points)
            const matchWon = this.score.us >= 7 || this.score.them >= 7;
            if (matchWon) {
                this.state = 'MATCH_OVER';
                this.showMatchResult(weWon);
            } else {
                this.showHandResult(weWon, points, resultType);
            }
        }

        showHandResult(weWon, points, resultType) {
            const rIcon = document.getElementById('resultIcon');
            const rTitle = document.getElementById('resultTitle');
            const rText = document.getElementById('resultText');
            const rsUs = document.getElementById('rsUs');
            const rsThem = document.getElementById('rsThem');
            const btnNext = document.getElementById('btnNext');

            rsUs.textContent = this.score.us;
            rsThem.textContent = this.score.them;
            btnNext.textContent = 'دست بعدی';

            if (weWon) {
                rIcon.textContent = resultType === 'hakem_kot' ? '👑⚡' : (resultType === 'kot' ? '🔥' : '🎉');
                rTitle.textContent = resultType === 'hakem_kot' ? 'حاکم کُت شدید! (+۳ امتیاز)' :
                                     (resultType === 'kot' ? 'کُت کردید! (+۲ امتیاز)' : 'تیم شما برنده این دست شد!');
                rText.textContent = `نتیجه دست: ${this.tricks.us} بر ${this.tricks.them}. ${points} امتیاز به شما اضافه شد.`;
            } else {
                rIcon.textContent = resultType === 'hakem_kot' ? '💀' : '💔';
                rTitle.textContent = resultType === 'hakem_kot' ? 'حاکم کُت شدید! (+۳ به حریف)' :
                                     (resultType === 'kot' ? 'کُت شدید! (+۲ به حریف)' : 'تیم حریف این دست را برد!');
                rText.textContent = `نتیجه دست: ${this.tricks.them} بر ${this.tricks.us}. ${points} امتیاز به حریف تعلق گرفت.`;
            }

            this.resultModal.classList.add('show');
        }

        showMatchResult(weWon) {
            const rIcon = document.getElementById('resultIcon');
            const rTitle = document.getElementById('resultTitle');
            const rText = document.getElementById('resultText');
            const rsUs = document.getElementById('rsUs');
            const rsThem = document.getElementById('rsThem');
            const btnNext = document.getElementById('btnNext');

            rsUs.textContent = this.score.us;
            rsThem.textContent = this.score.them;
            btnNext.textContent = 'شروع مسابقه جدید';

            if (weWon) {
                rIcon.textContent = '🏆';
                rTitle.textContent = 'قهرمان حکم رویال!';
                rText.textContent = `تبریک! شما و سارا با نتیجه ${this.score.us} بر ${this.score.them} حریف را شکست دادید!`;
                this.sound.winFanfare();
                this.confetti.blast(180);
            } else {
                rIcon.textContent = '🥈';
                rTitle.textContent = 'پایان بازی';
                rText.textContent = `تیم حریف با نتیجه ${this.score.them} بر ${this.score.us} بازی را به سود خود به پایان رساند.`;
            }

            this.resultModal.classList.add('show');
        }

        sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }
    }

    // ------------------------------------------------------------------------
    // Initialization on DOM Ready
    // ------------------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', () => {
        window.hokm = new HokmGame();
    });

})();
