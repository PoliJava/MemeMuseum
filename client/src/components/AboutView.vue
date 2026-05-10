<template>
  <div class="about-page">
    <!-- Page header -->
    <div class="about-header">
      <span class="about-slug">/about/</span>
      <h1 class="about-title">About the Museum</h1>
      <p class="about-sub">
        A living archive of internet culture, maintained for posterity.
      </p>
    </div>

    <!-- Mission -->
    <section class="about-section">
      <h2 class="section-heading">Mission Statement</h2>
      <div class="section-body">
        <p>
          MemeMuseum is an anonymous imageboard dedicated to the preservation,
          study, and discussion of internet memes as cultural artefacts. Richard
          Dawkins defined the "meme" as a unit of cultural transmission — an
          idea, behaviour, or style that spreads from person to person within a
          culture. Nowadays, memes are the dominant form of cultural expression
          online, shaping discourse, identity, and social dynamics. They express
          and shape popular culture and even influence political discourse.
        </p>
        <p>
          MemeMuseum is a digital archive that regards memes as cultural
          artifacts worthy of preservation and critique. Our interest is both
          humoristical and historical. We find memes funny, yet we feel the
          necessity to assess their cultural impact.
        </p>
      </div>
    </section>

    <!-- Boards -->
    <section class="about-section">
      <h2 class="section-heading">The Collection</h2>
      <div class="boards-grid">
        <div class="board-entry">
          <span class="board-slug">/cls/</span>
          <span class="board-name">Classics</span>
          <p class="board-desc">
            Pre-2013 artefacts and foundational works. Rage comics, early advice
            animals, image macros.
          </p>
        </div>
        <div class="board-entry">
          <span class="board-slug">/mdn/</span>
          <span class="board-name">Modern</span>
          <p class="board-desc">
            Post-2012 nostalgia and contemporary memes. Current memes allowed.
          </p>
        </div>
        <div class="board-entry">
          <span class="board-slug">/dank/</span>
          <span class="board-name">Dank</span>
          <p class="board-desc">
            Post-ironic, deep-fried, absurdist, and otherwise unclassifiable
            surrealist works.
          </p>
        </div>
        <div class="board-entry">
          <span class="board-slug">/oc/</span>
          <span class="board-name">Original Content</span>
          <p class="board-desc">
            New acquisitions. You can make dated memes here: inspiration from
            all eras is welcome.
          </p>
        </div>
        <div class="board-entry">
          <span class="board-slug">/bate/</span>
          <span class="board-name">Debate</span>
          <p class="board-desc">
            Critical discussion and historical analysis. Argue formats, trace
            lineages, debate the cultural impact of the works.
          </p>
        </div>
        <div class="board-entry">
          <span class="board-slug">/meta/</span>
          <span class="board-name">Meta</span>
          <p class="board-desc">
            Discussion of the museum itself — board organisation, rules,
            feedback, and bug reports.
          </p>
        </div>
      </div>
    </section>

    <!-- Rules -->
    <section class="about-section">
      <h2 class="section-heading">Rules of Conduct</h2>
      <ol class="rules-list">
        <li>Post content relevant to the board you are posting on.</li>
        <li>No reposts within 30 days on active boards.</li>
        <li>
          Provide context where possible — a meme without provenance doesn't
          leave much room for discussion.
        </li>
        <li>
          Bad-faith posts, flamewars, and personal attacks will be removed.
        </li>
        <li>
          Original content must be your own work. Do not claim authorship of
          others' creations.
        </li>
        <li>Curate responsibly. This is a museum, not a waste bin.</li>
      </ol>
    </section>

    <!-- Tech -->
    <section class="about-section">
      <h2 class="section-heading">Technical Notes</h2>
      <div class="section-body">
        <p>
          MemeMuseum is a full-stack web application built with
          <strong>Vue 3</strong> on the frontend and <strong>Laravel</strong> on
          the backend, backed by a <strong>PostgreSQL</strong> database.
          Authentication is handled via Laravel Sanctum (SPA session auth).
        </p>
        <p class="tech-notice">
          This project was developed as a university coursework submission. All
          user-submitted content remains the property of its respective
          creators.
        </p>
      </div>
    </section>
  </div>
</template>

<style scoped>
.about-page {
  max-width: 780px;
  margin: 0 auto;
  padding: 0 0 60px;
}

/* Page header*/
.about-header {
  padding: 28px 20px 22px;
  border-bottom: 3px solid var(--orange);
  margin-bottom: 32px;
}
.about-slug {
  font-family: var(--font-mono);
  font-size: 12px;
  color: var(--orange);
  letter-spacing: 1px;
  display: block;
  margin-bottom: 6px;
}
.about-title {
  font-family: var(--font-serif);
  font-size: 26px;
  color: var(--espresso);
  margin: 0 0 8px;
  font-weight: bold;
}
.about-sub {
  font-family: var(--font-mono);
  font-size: 12px;
  color: var(--grey-dk);
  margin: 0;
}

/* Sections*/
.about-section {
  margin-bottom: 36px;
  padding: 0 4px;
}

.section-heading {
  font-family: var(--font-serif);
  font-size: 15px;
  font-weight: bold;
  color: var(--brown-dk);
  text-transform: uppercase;
  letter-spacing: 1px;
  border-bottom: 1px solid var(--grey-lt);
  padding-bottom: 6px;
  margin: 0 0 16px;
}

.section-body p {
  font-family: var(--font-mono);
  font-size: 13px;
  line-height: 1.7;
  color: var(--espresso);
  margin: 0 0 12px;
}
.section-body p:last-child {
  margin-bottom: 0;
}

/* Boards grid*/
.boards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 10px;
}
.board-entry {
  background: var(--cream-dark);
  border: 1px solid var(--grey-lt);
  padding: 12px 14px;
}
.board-slug {
  font-family: var(--font-mono);
  font-size: 13px;
  font-weight: bold;
  color: var(--orange);
  display: block;
  margin-bottom: 2px;
}
.board-name {
  font-family: var(--font-serif);
  font-size: 13px;
  font-weight: bold;
  color: var(--espresso);
  display: block;
  margin-bottom: 6px;
}
.board-desc {
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--grey-dk);
  line-height: 1.55;
  margin: 0;
}
/* Rules*/
.rules-list {
  font-family: var(--font-mono);
  font-size: 13px;
  line-height: 1.8;
  color: var(--espresso);
  padding-left: 22px;
  margin: 0;
}
.rules-list li {
  margin-bottom: 4px;
}
.inline-link {
  color: var(--orange);
  text-decoration: none;
}
.inline-link:hover {
  text-decoration: underline;
}

/* Tech notice*/
.tech-notice {
  font-size: 11px !important;
  color: var(--grey) !important;
  font-style: italic;
  border-left: 2px solid var(--grey-lt);
  padding-left: 10px;
}

@media (max-width: 600px) {
  .about-header {
    padding: 20px 12px 16px;
  }
  .about-section {
    padding: 0;
  }
  .boards-grid {
    grid-template-columns: 1fr;
  }
}
</style>
