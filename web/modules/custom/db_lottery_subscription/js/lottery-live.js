(function (Drupal, once) {
  function random(min, max) {
    return Math.random() * (max - min) + min;
  }

  function createBalloonField(container) {
    const field = document.createElement('div');
    field.className = 'lottery-live__balloons';

    const colors = ['#ff6b6b', '#ffd166', '#06d6a0', '#4dabf7', '#f06595', '#ff922b'];
    for (let index = 0; index < 14; index += 1) {
      const balloon = document.createElement('div');
      balloon.className = 'lottery-live__balloon';
      balloon.style.left = `${random(4, 94)}%`;
      balloon.style.animationDuration = `${random(6.5, 10.5)}s`;
      balloon.style.animationDelay = `${random(0, 1.5)}s`;
      balloon.style.background = colors[index % colors.length];
      field.appendChild(balloon);
    }

    container.appendChild(field);
  }

  function createFireworks(container) {
    const canvas = document.createElement('canvas');
    canvas.className = 'lottery-live__fireworks';
    container.appendChild(canvas);

    const context = canvas.getContext('2d');
    const bounds = container.getBoundingClientRect();
    canvas.width = bounds.width * window.devicePixelRatio;
    canvas.height = bounds.height * window.devicePixelRatio;
    canvas.style.width = `${bounds.width}px`;
    canvas.style.height = `${bounds.height}px`;
    context.scale(window.devicePixelRatio, window.devicePixelRatio);

    const bursts = Array.from({ length: 7 }, (_, index) => ({
      x: random(bounds.width * 0.14, bounds.width * 0.86),
      y: random(bounds.height * 0.12, bounds.height * 0.55),
      delay: index * 280,
      hue: random(0, 360),
    }));

    const particles = [];
    bursts.forEach((burst) => {
      for (let i = 0; i < 24; i += 1) {
        particles.push({
          x: burst.x,
          y: burst.y,
          angle: (Math.PI * 2 * i) / 24,
          speed: random(1.2, 4.8),
          radius: random(1.5, 3.4),
          life: random(48, 72),
          maxLife: random(48, 72),
          delay: burst.delay,
          color: `hsl(${burst.hue + random(-22, 22)}deg 96% ${random(58, 72)}%)`,
        });
      }
    });

    let frame = 0;
    let rafId = null;

    const tick = () => {
      frame += 1;
      context.clearRect(0, 0, bounds.width, bounds.height);

      let hasAliveParticle = false;
      particles.forEach((particle) => {
        if (frame < particle.delay || particle.life <= 0) {
          return;
        }

        hasAliveParticle = true;
        const progress = 1 - (particle.life / particle.maxLife);
        const distance = progress * particle.speed * 18;
        const x = particle.x + Math.cos(particle.angle) * distance;
        const y = particle.y + Math.sin(particle.angle) * distance + progress * 38;

        context.beginPath();
        context.fillStyle = particle.color;
        context.globalAlpha = particle.life / particle.maxLife;
        context.arc(x, y, particle.radius, 0, Math.PI * 2);
        context.fill();

        particle.life -= 1;
      });
      context.globalAlpha = 1;

      if (hasAliveParticle) {
        rafId = window.requestAnimationFrame(tick);
      }
      else {
        canvas.remove();
      }
    };

    rafId = window.requestAnimationFrame(tick);
    window.setTimeout(() => {
      if (rafId) {
        window.cancelAnimationFrame(rafId);
      }
      canvas.remove();
    }, 7000);
  }

  function launchCelebration(element) {
    const overlay = document.createElement('div');
    overlay.className = 'lottery-live__celebration';
    element.appendChild(overlay);

    createBalloonField(overlay);
    createFireworks(overlay);

    window.setTimeout(() => {
      overlay.remove();
    }, 9000);
  }

  Drupal.behaviors.dbLotteryLiveDraw = {
    attach(context) {
      once('dbLotteryLiveSubmit', '[data-lottery-live-submit]', context).forEach((button) => {
        button.addEventListener('click', () => {
          button.classList.add('is-pending');
        });
      });

      once('dbLotteryLiveCelebration', '[data-lottery-live-celebration-id]', context).forEach((element) => {
        if (!element.dataset.lotteryLiveCelebrationId) {
          return;
        }
        launchCelebration(element);
      });
    },
  };
})(Drupal, once);
