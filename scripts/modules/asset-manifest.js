const promptPath = 'imagensPrompt/';

export const IMAGE_ASSETS = {
  floatingElement: { src: `${promptPath}imagem01.png`, alt: 'Elemento narrativo voador' },
  kitchen: {
    background: { src: `${promptPath}imagem04.jpg`, alt: 'Plano de fundo ilustrado da cozinha temporal' },
    architecture: { src: `${promptPath}imagensIntercambiáveis.png`, alt: 'Arquitetura desenhada da cozinha em camada transparente' },
    table: { src: `${promptPath}itemDeNavegaçãoDoCarrossel01.png`, alt: 'Mesa e utensílios desenhados em primeiro plano' },
    oven: { src: `${promptPath}itemdenavegaçãoimagensintercambiaveis.png`, alt: 'Módulo de forno e mecanismo em PNG transparente' },
    props: { src: `${promptPath}imagensIntercambiáveis.png`, alt: 'Objetos intercambiáveis da cozinha temporal' }
  },
  characters: {
    hagia: { src: `${promptPath}imagem01.png`, alt: 'Hagia em leitura narrativa' },
    pio: { src: `${promptPath}itemdenavegaçãoimagensintercambiaveis.png`, alt: 'Pio em deslocamento' },
    abuela: { src: `${promptPath}itemDeNavegaçãoDoCarrossel01.png`, alt: 'Abuela como memória visual' }
  },
  panels: {
    continuity: { src: `${promptPath}more_hero__gbpl7ki780i2_xlarge.jpg`, alt: 'Vestígios sobre a mesa' },
    mechanism: { src: `${promptPath}imagensIntercambiáveis.jpg`, alt: 'Mecanismo intercambiável' },
    coexistence: { src: `${promptPath}imagem04.jpg`, alt: 'Coexistência temporal' },
    versatility: { src: `${promptPath}versatility_hero__el4o6rn9q24i_xlarge.jpg`, alt: 'Versatilidade do mecanismo' },
    collective: { src: `${promptPath}imagem02.jpg`, alt: 'Participação coletiva' },
    speed: { src: `${promptPath}running_5g__5ll87hrde76q_large.jpg`, alt: 'Velocidade narrativa' },
    coordinated: { src: `${promptPath}running_workout_buddy__cnimtgypraj6_large.jpg`, alt: 'Ação coordenada' },
    safety: { src: `${promptPath}safety_hero__b23icntx4v36_xlarge.jpg`, alt: 'Coerência temporal' },
    mechanismsBand: { src: `${promptPath}even_more_apps_01__exq83khgstkm_large.jpg`, alt: 'Mecanismos conectados' }
  }
};

export const assets = {
  heroKitchen: IMAGE_ASSETS.kitchen.background.src,
  mechanism: IMAGE_ASSETS.panels.mechanism.src,
  coexistence: IMAGE_ASSETS.panels.coexistence.src,
  versatility: IMAGE_ASSETS.panels.versatility.src,
  collective: IMAGE_ASSETS.panels.collective.src,
  speed: IMAGE_ASSETS.panels.speed.src,
  coordinated: IMAGE_ASSETS.panels.coordinated.src,
  continuity: IMAGE_ASSETS.panels.continuity.src,
  safety: IMAGE_ASSETS.panels.safety.src,
  mechanismsBand: IMAGE_ASSETS.panels.mechanismsBand.src,
  floatingElement: IMAGE_ASSETS.floatingElement.src,
  kitchenBackground: IMAGE_ASSETS.kitchen.background.src,
  kitchenArchitecture: IMAGE_ASSETS.kitchen.architecture.src,
  kitchenTable: IMAGE_ASSETS.kitchen.table.src,
  kitchenOven: IMAGE_ASSETS.kitchen.oven.src,
  kitchenProps: IMAGE_ASSETS.kitchen.props.src
};