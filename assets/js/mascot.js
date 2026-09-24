/* ==================== STUDY BUDDY MASCOT ====================
   Include after index.js. Call window.initMascot(data) with the
   same `data` object passed to displayStudentInfo() once a
   student's records have loaded, e.g. at the end of
   displayStudentInfo(): if (typeof initMascot === 'function') initMascot(data);
================================================================= */

(function () {
  const MESSAGES = {
    excited: [
      "Wow, {cgpa} CGPA?! You're an absolute topper! 🏆",
      "Look at you go! Keep this streak alive 🌟",
      "That's elite-level work. Proud of you! 🎉",
      "Certificates, offers, grades... you're stacking wins! 💎",
      "Congratulations on your outstanding performance! 🎓",
      "You're absolutely crushing it! Keep up the excellence! ⭐"
    ],
    happy: [
      "Solid CGPA of {cgpa} — nice and steady! 👏",
      "You're doing genuinely well, keep it up!",
      "Attendance and grades both looking healthy 🌿",
      "Consistency like this pays off. Nice work!"
    ],
    neutral: [
      "You're doing okay — a little extra push and you'll shine ✨",
      "Steady as it goes. What's one thing to improve today?",
      "Small consistent effort beats last-minute cramming!",
      "Halfway there — don't lose momentum now."
    ],
    concerned: [
      "A backlog or two? Let's clear those next exam 💪",
      "Attendance is a bit shaky — try not to miss more classes.",
      "You've got this, but it's time to focus a little harder.",
      "Talk to your faculty advisor if you're stuck — that's what they're there for."
    ],
    worried: [
      "Attendance is below 65% — that's detention territory ⚠️",
      "Please don't skip any more classes this month!",
      "This is fixable, but only if you act now. Attend every class!",
      "Reach out to your HOD about condonation before it's too late."
    ],
    idle: [
      "Hi! Search a roll number and I'll cheer you on 👋",
      "I'm your study buddy — I react to your stats!",
      "Looking for someone? Try the search bar above 🔍",
      "Tap me anytime for a quick pep talk."
    ]
  };

  const STUDY_TIPS = {
    excited: [
      "💡 Tip: Consider mentoring others — teaching reinforces your own learning!",
      "💡 Tip: Your consistency is amazing — document your study routine to help others!",
      "💡 Tip: You're ready for advanced topics — explore research opportunities!",
      "💡 Tip: Consider internships or projects to apply your knowledge practically!"
    ],
    happy: [
      "💡 Tip: Focus on understanding concepts deeply, not just memorizing.",
      "💡 Tip: Practice past papers to identify your weak areas early.",
      "💡 Tip: Form study groups to discuss complex topics with peers.",
      "💡 Tip: Use spaced repetition for long-term retention of key concepts."
    ],
    neutral: [
      "💡 Tip: Set specific daily study goals to maintain consistency.",
      "💡 Tip: Review your notes within 24 hours of each lecture.",
      "💡 Tip: Break difficult subjects into smaller, manageable chunks.",
      "💡 Tip: Use the Pomodoro technique: 25 min focus, 5 min break."
    ],
    concerned: [
      "💡 Tip: Prioritize clearing backlogs before they accumulate further.",
      "💡 Tip: Attend all classes — each lecture builds on the previous one.",
      "💡 Tip: Meet with professors during office hours for clarification.",
      "💡 Tip: Create a realistic study schedule and stick to it."
    ],
    worried: [
      "💡 Tip: Attendance is critical — attend every single class from now on.",
      "💡 Tip: Request a meeting with your HOD immediately about attendance issues.",
      "💡 Tip: Focus on understanding the most important concepts first.",
      "💡 Tip: Seek help from classmates who have good attendance records."
    ],
    idle: [
      "💡 Tip: Start with small, consistent study habits rather than cramming.",
      "💡 Tip: Find a study environment that works best for you.",
      "💡 Tip: Use active recall instead of passive reading.",
      "💡 Tip: Stay organized with a planner or study app."
    ]
  };

  const ACHIEVEMENTS = {
    perfect_attendance: { icon: '🎯', name: 'Perfect Attendance', condition: (stats) => stats.attendance >= 95 },
    excellent_attendance: { icon: '📈', name: 'Excellent Attendance', condition: (stats) => stats.attendance >= 85 },
    no_backlogs: { icon: '✅', name: 'No Backlogs', condition: (stats) => stats.backlogs === 0 },
    high_achiever: { icon: '🏆', name: 'High Achiever', condition: (stats) => stats.cgpa >= 9 },
    consistent_performer: { icon: '⭐', name: 'Consistent Performer', condition: (stats) => stats.cgpa >= 8 },
    certified: { icon: '📜', name: 'NPTEL Certified', condition: (stats) => stats.achievements > 0 },
    scholar: { icon: '🎓', name: 'Scholar', condition: (stats) => stats.cgpa >= 7 && stats.attendance >= 75 }
  };

  const ROTATE_MS = 30000;
  const AUTO_HIDE_MS = 7000;

  let widget, bubble, bubbleText, character;
  let mood = 'idle';
  let msgIndex = 0;
  let rotateTimer = null;
  let hideTimer = null;
  let dismissedByUser = false;
  let audioContext = null;
  let flareTimer = null;

  // Simple sound generator using Web Audio API
  function playSound(type) {
    try {
      if (!audioContext) {
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
      }

      const oscillator = audioContext.createOscillator();
      const gainNode = audioContext.createGain();

      oscillator.connect(gainNode);
      gainNode.connect(audioContext.destination);

      // Use different waveforms for variety
      oscillator.type = 'sine';

      const now = audioContext.currentTime;

      switch (type) {
        case 'appear':
          // Magical sparkle sound
          oscillator.type = 'triangle';
          oscillator.frequency.setValueAtTime(880, now);
          oscillator.frequency.exponentialRampToValueAtTime(1760, now + 0.1);
          oscillator.frequency.exponentialRampToValueAtTime(880, now + 0.2);
          gainNode.gain.setValueAtTime(0.2, now);
          gainNode.gain.exponentialRampToValueAtTime(0.01, now + 0.3);
          oscillator.start(now);
          oscillator.stop(now + 0.3);
          break;
        case 'click':
          // Funny boing sound
          oscillator.type = 'square';
          oscillator.frequency.setValueAtTime(200, now);
          oscillator.frequency.exponentialRampToValueAtTime(600, now + 0.05);
          oscillator.frequency.exponentialRampToValueAtTime(200, now + 0.1);
          gainNode.gain.setValueAtTime(0.15, now);
          gainNode.gain.exponentialRampToValueAtTime(0.01, now + 0.15);
          oscillator.start(now);
          oscillator.stop(now + 0.15);
          break;
        case 'excited':
          // Celebratory fanfare with horn
          oscillator.type = 'triangle';
          oscillator.frequency.setValueAtTime(523.25, now);
          oscillator.frequency.setValueAtTime(659.25, now + 0.1);
          oscillator.frequency.setValueAtTime(783.99, now + 0.2);
          oscillator.frequency.setValueAtTime(1046.50, now + 0.3);
          gainNode.gain.setValueAtTime(0.2, now);
          gainNode.gain.exponentialRampToValueAtTime(0.01, now + 0.4);
          oscillator.start(now);
          oscillator.stop(now + 0.4);

          // Add horn sound for extra celebration
          setTimeout(() => {
            try {
              if (!audioContext) {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
              }
              const hornOsc = audioContext.createOscillator();
              const hornGain = audioContext.createGain();
              hornOsc.connect(hornGain);
              hornGain.connect(audioContext.destination);

              hornOsc.type = 'sawtooth';
              const hornNow = audioContext.currentTime;
              hornOsc.frequency.setValueAtTime(261.63, hornNow);
              hornOsc.frequency.setValueAtTime(329.63, hornNow + 0.15);
              hornOsc.frequency.setValueAtTime(392.00, hornNow + 0.3);
              hornOsc.frequency.setValueAtTime(523.25, hornNow + 0.45);
              hornGain.gain.setValueAtTime(0.15, hornNow);
              hornGain.gain.exponentialRampToValueAtTime(0.01, hornNow + 0.6);
              hornOsc.start(hornNow);
              hornOsc.stop(hornNow + 0.6);
            } catch (e) {
              // Horn sound error - silently ignore
            }
          }, 50);
          break;
        case 'happy':
          // Cheerful bloop
          oscillator.type = 'sine';
          oscillator.frequency.setValueAtTime(400, now);
          oscillator.frequency.exponentialRampToValueAtTime(600, now + 0.08);
          oscillator.frequency.exponentialRampToValueAtTime(500, now + 0.16);
          gainNode.gain.setValueAtTime(0.2, now);
          gainNode.gain.exponentialRampToValueAtTime(0.01, now + 0.2);
          oscillator.start(now);
          oscillator.stop(now + 0.2);
          break;
        case 'concerned':
          // Ponderous hmm
          oscillator.type = 'sawtooth';
          oscillator.frequency.setValueAtTime(300, now);
          oscillator.frequency.setValueAtTime(250, now + 0.15);
          oscillator.frequency.setValueAtTime(280, now + 0.3);
          gainNode.gain.setValueAtTime(0.1, now);
          gainNode.gain.exponentialRampToValueAtTime(0.01, now + 0.35);
          oscillator.start(now);
          oscillator.stop(now + 0.35);
          break;
        case 'worried':
          // Dramatic dun-dun-dun
          oscillator.type = 'square';
          oscillator.frequency.setValueAtTime(200, now);
          oscillator.frequency.setValueAtTime(150, now + 0.1);
          oscillator.frequency.setValueAtTime(100, now + 0.2);
          gainNode.gain.setValueAtTime(0.15, now);
          gainNode.gain.exponentialRampToValueAtTime(0.01, now + 0.3);
          oscillator.start(now);
          oscillator.stop(now + 0.3);
          break;
        case 'neutral':
          // Friendly ping
          oscillator.type = 'sine';
          oscillator.frequency.setValueAtTime(440, now);
          oscillator.frequency.exponentialRampToValueAtTime(550, now + 0.1);
          gainNode.gain.setValueAtTime(0.15, now);
          gainNode.gain.exponentialRampToValueAtTime(0.01, now + 0.15);
          oscillator.start(now);
          oscillator.stop(now + 0.15);
          break;
        case 'dismiss':
          // Poof sound
          oscillator.type = 'triangle';
          oscillator.frequency.setValueAtTime(800, now);
          oscillator.frequency.exponentialRampToValueAtTime(200, now + 0.1);
          gainNode.gain.setValueAtTime(0.15, now);
          gainNode.gain.exponentialRampToValueAtTime(0.01, now + 0.15);
          oscillator.start(now);
          oscillator.stop(now + 0.15);
          break;
      }
    } catch (e) {
      // Audio not supported or blocked - silently ignore
    }
  }

  // Create and shoot flares
  function shootFlare() {
    if (!widget) return;

    const flare = document.createElement('div');
    const flareType = Math.floor(Math.random() * 8) + 1;
    flare.className = `mascot-flare flare-${flareType}`;

    // Random animation type
    const animTypes = ['active', 'burst', 'spiral'];
    const animType = animTypes[Math.floor(Math.random() * animTypes.length)];
    flare.classList.add(animType);

    // Random direction with more dramatic distance
    const angle = Math.random() * Math.PI * 2;
    const distance = 80 + Math.random() * 100;
    const x = Math.cos(angle) * distance;
    const y = Math.sin(angle) * distance;

    flare.style.setProperty('--flare-x', `${x}px`);
    flare.style.setProperty('--flare-y', `${y}px`);

    // Position at character center
    const characterRect = character.getBoundingClientRect();
    const widgetRect = widget.getBoundingClientRect();
    const centerX = characterRect.left - widgetRect.left + characterRect.width / 2;
    const centerY = characterRect.top - widgetRect.top + characterRect.height / 2;

    flare.style.left = `${centerX}px`;
    flare.style.top = `${centerY}px`;

    widget.appendChild(flare);

    // Trigger animation
    requestAnimationFrame(() => {
      flare.classList.add('active');
    });

    // Remove after animation
    setTimeout(() => {
      flare.remove();
    }, 1200);
  }

  function scheduleFlares() {
    clearInterval(flareTimer);
    flareTimer = setInterval(() => {
      if (mood === 'excited' || mood === 'happy') {
        // Shoot multiple flares for more dramatic effect
        const flareCount = mood === 'excited' ? 3 : 2;
        for (let i = 0; i < flareCount; i++) {
          setTimeout(() => shootFlare(), i * 100);
        }
      }
    }, 1500);
  }

  // Confetti explosion for CGPA 9+ students
  function explodeConfetti() {
    if (!widget) return;

    const confettiCount = 75;
    for (let i = 0; i < confettiCount; i++) {
      const confetti = document.createElement('div');
      const colorClass = `c${(i % 25) + 1}`;
      confetti.className = `mascot-confetti ${colorClass}`;

      // Random direction and distance
      const angle = Math.random() * Math.PI * 2;
      const distance = 100 + Math.random() * 200;
      const x = Math.cos(angle) * distance;
      const y = Math.sin(angle) * distance;

      confetti.style.setProperty('--confetti-x', `${x}px`);
      confetti.style.setProperty('--confetti-y', `${y}px`);

      // Position at character center
      const characterRect = character.getBoundingClientRect();
      const widgetRect = widget.getBoundingClientRect();
      const centerX = characterRect.left - widgetRect.left + characterRect.width / 2;
      const centerY = characterRect.top - widgetRect.top + characterRect.height / 2;

      confetti.style.left = `${centerX}px`;
      confetti.style.top = `${centerY}px`;

      widget.appendChild(confetti);

      // Trigger animation with staggered timing
      setTimeout(() => {
        confetti.classList.add('active');
      }, Math.random() * 200);

      // Remove after animation
      setTimeout(() => {
        confetti.remove();
      }, 2200);
    }
  }

  function svgMarkup() {
    return `
      <svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
        <circle class="mascot-sparkle s1" cx="12" cy="20" r="3" fill="#fde68a"/>
        <circle class="mascot-sparkle s2" cx="108" cy="30" r="2.4" fill="#fde68a"/>
        <circle class="mascot-sparkle s3" cx="16" cy="90" r="2.2" fill="#fde68a"/>

        <path class="mascot-ear" d="M30 34 L20 10 L44 26 Z"/>
        <path class="mascot-ear" d="M90 34 L100 10 L76 26 Z"/>

        <!-- Graduation Hat (hidden by default) -->
        <g class="mascot-hat" style="display: none;">
          <!-- Hat base -->
          <path d="M35 28 L60 18 L85 28 L60 38 Z" fill="#1e3a8a" stroke="#1e40af" stroke-width="1"/>
          <!-- Hat top -->
          <rect x="35" y="24" width="50" height="6" fill="#1e3a8a" stroke="#1e40af" stroke-width="1"/>
          <!-- Tassel -->
          <circle cx="60" cy="21" r="3" fill="#fbbf24"/>
          <path d="M60 24 L60 35" stroke="#fbbf24" stroke-width="2"/>
          <path d="M60 35 L55 40" stroke="#fbbf24" stroke-width="2"/>
          <path d="M60 35 L65 40" stroke="#fbbf24" stroke-width="2"/>
        </g>

        <!-- Gold Medal (CGPA 9+) -->
        <g class="mascot-medal medal-gold" style="display: none;">
          <circle cx="85" cy="45" r="8" fill="#fbbf24" stroke="#f59e0b" stroke-width="2"/>
          <circle cx="85" cy="45" r="5" fill="#fbbf24" stroke="#f59e0b" stroke-width="1"/>
          <path d="M85 38 L85 52" stroke="#f59e0b" stroke-width="2"/>
          <path d="M82 38 L88 38" stroke="#f59e0b" stroke-width="2"/>
          <text x="85" y="47" font-size="6" text-anchor="middle" fill="#92400e" font-weight="bold">★</text>
        </g>

        <!-- Silver Medal (CGPA 8-8.9) -->
        <g class="mascot-medal medal-silver" style="display: none;">
          <circle cx="85" cy="45" r="8" fill="#e5e7eb" stroke="#9ca3af" stroke-width="2"/>
          <circle cx="85" cy="45" r="5" fill="#e5e7eb" stroke="#9ca3af" stroke-width="1"/>
          <path d="M85 38 L85 52" stroke="#9ca3af" stroke-width="2"/>
          <path d="M82 38 L88 38" stroke="#9ca3af" stroke-width="2"/>
          <text x="85" y="47" font-size="6" text-anchor="middle" fill="#6b7280" font-weight="bold">★</text>
        </g>

        <!-- Bronze Medal (CGPA 7-7.9) -->
        <g class="mascot-medal medal-bronze" style="display: none;">
          <circle cx="85" cy="45" r="8" fill="#d97706" stroke="#b45309" stroke-width="2"/>
          <circle cx="85" cy="45" r="5" fill="#d97706" stroke="#b45309" stroke-width="1"/>
          <path d="M85 38 L85 52" stroke="#b45309" stroke-width="2"/>
          <path d="M82 38 L88 38" stroke="#b45309" stroke-width="2"/>
          <text x="85" y="47" font-size="6" text-anchor="middle" fill="#78350f" font-weight="bold">★</text>
        </g>

        <g class="mascot-antenna-star" transform="translate(60,10)">
          <path d="M0,-9 L2.1,-2.7 L9,-2.7 L3.4,1.1 L5.5,7.9 L0,3.6 L-5.5,7.9 L-3.4,1.1 L-9,-2.7 L-2.1,-2.7 Z" fill="#fbbf24"/>
        </g>

        <circle class="mascot-body" cx="60" cy="66" r="42"/>

        <ellipse class="mascot-blush" cx="34" cy="72" rx="7" ry="4.5"/>
        <ellipse class="mascot-blush" cx="86" cy="72" rx="7" ry="4.5"/>

        <g>
          <ellipse class="mascot-eye-white" cx="44" cy="60" rx="10" ry="12"/>
          <ellipse class="mascot-eye-white" cx="76" cy="60" rx="10" ry="12"/>
          <circle class="mascot-eye-pupil left" cx="45" cy="62" r="4.6"/>
          <circle class="mascot-eye-pupil right" cx="77" cy="62" r="4.6"/>
        </g>

        <path class="mascot-brow" d="M35 46 L52 50" stroke="currentColor" stroke-width="3" stroke-linecap="round" fill="none"/>
        <path class="mascot-brow" d="M85 46 L68 50" stroke="currentColor" stroke-width="3" stroke-linecap="round" fill="none"/>

        <path class="mascot-mouth mouth-excited" d="M42 82 Q60 102 78 82 Q60 94 42 82 Z" fill="#7c2d12"/>
        <path class="mascot-mouth mouth-happy" d="M44 82 Q60 94 76 82" stroke="#7c2d12" stroke-width="3.4" stroke-linecap="round" fill="none"/>
        <path class="mascot-mouth mouth-neutral" d="M47 85 L73 85" stroke="#7c2d12" stroke-width="3.4" stroke-linecap="round" fill="none"/>
        <path class="mascot-mouth mouth-concerned" d="M46 89 Q60 82 74 89" stroke="#7c2d12" stroke-width="3.2" stroke-linecap="round" fill="none"/>
        <path class="mascot-mouth mouth-worried" d="M45 91 Q60 80 75 91" stroke="#7c2d12" stroke-width="3.4" stroke-linecap="round" fill="none"/>
        
        <!-- Additional expressions -->
        <path class="mascot-mouth mouth-surprised" d="M55 82 Q60 95 65 82 Q60 88 55 82 Z" fill="#7c2d12"/>
        <path class="mascot-mouth mouth-thinking" d="M50 88 Q60 82 70 88" stroke="#7c2d12" stroke-width="3" stroke-linecap="round" fill="none"/>
        <path class="mascot-mouth mouth-wink" d="M48 85 L72 85" stroke="#7c2d12" stroke-width="3" stroke-linecap="round" fill="none"/>
        <path class="mascot-mouth mouth-sleepy" d="M50 86 Q60 90 70 86" stroke="#7c2d12" stroke-width="2.5" stroke-linecap="round" fill="none"/>
        
        <!-- Additional eye expressions -->
        <ellipse class="mascot-eye-white eye-wink-left" cx="44" cy="60" rx="10" ry="12" style="display: none;"/>
        <path class="mascot-eye-wink-closed" d="M34 60 Q44 55 54 60" stroke="#7c2d12" stroke-width="3" stroke-linecap="round" fill="none" style="display: none;"/>
        
        <!-- Sleepy eyes -->
        <ellipse class="mascot-eye-white eye-sleepy" cx="44" cy="62" rx="10" ry="8" style="display: none;"/>
        <ellipse class="mascot-eye-white eye-sleepy" cx="76" cy="62" rx="10" ry="8" style="display: none;"/>
        <circle class="mascot-eye-pupil eye-sleepy" cx="45" cy="62" r="4" style="display: none;"/>
        <circle class="mascot-eye-pupil eye-sleepy" cx="77" cy="62" r="4" style="display: none;"/>

        <path class="mascot-sweat" d="M92 48 Q97 56 92 60 Q87 56 92 48 Z" fill="#7dd3fc"/>
      </svg>`;
  }

  function buildWidget() {
    if (document.getElementById('mascotWidget')) return;
    widget = document.createElement('div');
    widget.className = 'mascot-widget';
    widget.id = 'mascotWidget';
    widget.innerHTML = `
      <div class="mascot-particle"></div>
      <div class="mascot-particle"></div>
      <div class="mascot-particle"></div>
      <div class="mascot-particle"></div>
      <div class="mascot-particle"></div>
      <div class="mascot-bubble" id="mascotBubble">
        <span class="mascot-bubble-text" id="mascotBubbleText"></span>
        <button class="mascot-bubble-close" id="mascotBubbleClose" aria-label="Dismiss">&times;</button>
      </div>
      <button class="mascot-toggle" id="mascotToggle" title="Show study buddy">💬</button>
      <div class="mascot-character mood-idle" id="mascotCharacter" title="Click me for a tip!">
        ${svgMarkup()}
      </div>
    `;
    document.body.appendChild(widget);

    bubble = document.getElementById('mascotBubble');
    bubbleText = document.getElementById('mascotBubbleText');
    character = document.getElementById('mascotCharacter');

    character.addEventListener('click', () => {
      playSound('click');
      shootFlare();
      dismissedByUser = false;
      msgIndex++;
      showMessage();
    });

    document.getElementById('mascotBubbleClose').addEventListener('click', (e) => {
      e.stopPropagation();
      playSound('dismiss');
      hideBubble();
      dismissedByUser = true;
    });

    document.getElementById('mascotToggle').addEventListener('click', () => {
      widget.classList.remove('collapsed');
    });
  }

  function fillTemplate(text, stats) {
    return text
      .replace('{cgpa}', stats.cgpa != null ? stats.cgpa.toFixed(2) : '')
      .replace('{attendance}', stats.attendance != null ? stats.attendance + '%' : '');
  }

  function showBubble(text) {
    bubbleText.textContent = text;
    bubble.classList.add('show');
    clearTimeout(hideTimer);
    hideTimer = setTimeout(hideBubble, AUTO_HIDE_MS);
  }

  function hideBubble() {
    bubble.classList.remove('show');
  }

  let lastStats = {};
  function checkAchievements(stats) {
    const earnedAchievements = [];
    for (const [key, achievement] of Object.entries(ACHIEVEMENTS)) {
      if (achievement.condition(stats)) {
        earnedAchievements.push(achievement);
      }
    }
    return earnedAchievements;
  }

  function showAchievements(achievements) {
    if (achievements.length === 0) return;

    const achievementText = achievements.map(a => `${a.icon} ${a.name}`).join(', ');

    // Show achievement notification occasionally
    if (msgIndex % 5 === 0 && achievements.length > 0) {
      const randomAchievement = achievements[Math.floor(Math.random() * achievements.length)];
      return `🏅 Achievement: ${randomAchievement.icon} ${randomAchievement.name}`;
    }
    return null;
  }

  function pickMessage(m) {
    const pool = MESSAGES[m] || MESSAGES.idle;
    const tips = STUDY_TIPS[m] || STUDY_TIPS.idle;

    // Every 3rd message, show a study tip instead
    if (msgIndex % 3 === 2) {
      return tips[msgIndex % tips.length];
    }
    return pool[msgIndex % pool.length];
  }

  function showMessage() {
    const text = fillTemplate(pickMessage(mood), lastStats);
    showBubble(text);
  }

  function scheduleRotation() {
    clearInterval(rotateTimer);
    rotateTimer = setInterval(() => {
      if (dismissedByUser) return;
      msgIndex++;
      showMessage();
    }, ROTATE_MS);
  }

  function setMood(newMood) {
    if (mood !== newMood) {
      playSound(newMood);

      // Trigger confetti explosion for excited mood (CGPA 9+)
      if (newMood === 'excited') {
        setTimeout(() => explodeConfetti(), 300);
      }
    }
    mood = newMood;
    character.className = 'mascot-character mood-' + newMood;
    widget.className = 'mascot-widget mood-' + newMood;

    // Show graduation hat for excited mood (CGPA 9+)
    const hat = character.querySelector('.mascot-hat');
    if (hat) {
      hat.style.display = newMood === 'excited' ? 'block' : 'none';
    }

    // Show medals based on mood/CGPA level
    const goldMedal = character.querySelector('.medal-gold');
    const silverMedal = character.querySelector('.medal-silver');
    const bronzeMedal = character.querySelector('.medal-bronze');

    if (goldMedal) goldMedal.style.display = 'none';
    if (silverMedal) silverMedal.style.display = 'none';
    if (bronzeMedal) bronzeMedal.style.display = 'none';

    if (newMood === 'excited' && goldMedal) {
      goldMedal.style.display = 'block';
    } else if (newMood === 'happy' && silverMedal) {
      silverMedal.style.display = 'block';
    } else if (newMood === 'neutral' && bronzeMedal) {
      bronzeMedal.style.display = 'block';
    }
  }

  /* ---- Analyze the same `data` object used by displayStudentInfo ---- */
  function analyzeStudentData(data) {
    const stats = { cgpa: null, attendance: null, backlogs: 0, achievements: 0 };

    // CGPA is in data.cgpa_rank.cgpa
    if (data && data.cgpa_rank && data.cgpa_rank.cgpa != null) {
      stats.cgpa = Number(data.cgpa_rank.cgpa);
    }

    if (data && data.attendance_groups) {
      // Attendance data is in data.attendance_groups array (same as displayStudentInfo uses)
      const attendanceGroups = data.attendance_groups || [];

      if (attendanceGroups.length) {
        // Get the most recent attendance group (first one should be latest)
        const latest = attendanceGroups[0];

        // Calculate percentage from summary data
        const summary = latest.summary || [];
        if (summary.length) {
          const latestSummary = summary[0];

          let pct = null;
          if (latestSummary.percentage != null) {
            pct = Number(latestSummary.percentage);
          }

          if (Number.isFinite(pct) && pct >= 0 && pct <= 100) {
            stats.attendance = pct;
          }
        }
      }
    }

    if (data && data.tables) {
      stats.achievements =
        (data.tables['nptel_certificates'] || []).length +
        (data.tables['job_offers'] || []).length;
    }

    // Only check backlogs if CGPA is not available (latest CGPA means no backlogs)
    if (!stats.cgpa) {
      const subjectResults = data.tables['subject_results'] || [];
      stats.backlogs = subjectResults.filter(r => {
        const g = String(r.grade || '').trim().toUpperCase();
        // Only count if grade is explicitly F and not empty/null
        if (!g || g === '' || g === 'N/A' || g === '-') return false;
        return g === 'F' || g.startsWith('F');
      }).length;
    }

    let m = 'neutral';
    // Prioritize CGPA-based moods over attendance
    if (stats.cgpa != null && stats.cgpa >= 9) {
      m = 'excited';
    } else if ((stats.cgpa != null && stats.cgpa >= 8) || stats.achievements > 0) {
      m = 'happy';
    } else if (stats.attendance != null && stats.attendance < 65) {
      m = 'worried';
    } else if (stats.backlogs > 0 || (stats.attendance != null && stats.attendance < 75)) {
      m = 'concerned';
    } else if (stats.cgpa != null && stats.cgpa >= 6.5) {
      m = 'neutral';
    }

    return { mood: m, stats };
  }

  function initMascot(data) {
    buildWidget();
    widget.classList.remove('collapsed');
    playSound('appear');
    scheduleFlares();
    const { mood: newMood, stats } = analyzeStudentData(data);
    lastStats = stats;

    // Check achievements
    const achievements = checkAchievements(stats);

    msgIndex = 0;
    dismissedByUser = false;
    setMood(newMood);
    setTimeout(showMessage, 400);
    scheduleRotation();
  }

  window.initMascot = initMascot;

  document.addEventListener('DOMContentLoaded', () => {
    buildWidget();
    setMood('idle');
    setTimeout(showMessage, 900);
    scheduleRotation();
  });
})();
