const promptPath = '/tempo/images/';

export const IMAGE_ASSETS = {
  floatingElement: { src: `${promptPath}imagem1.png`, alt: 'Elemento narrativo voador' },
  kitchen: {
    background: { src: `${promptPath}imagem4.png`, alt: 'Plano de fundo ilustrado da cozinha temporal' },
    architecture: { src: `${promptPath}imagem013.png`, alt: 'Arquitetura desenhada da cozinha em camada transparente' },
    table: { src: `${promptPath}imagem010.png`, alt: 'Mesa e utensílios desenhados em primeiro plano' },
    oven: { src: `${promptPath}imagem014.png`, alt: 'Módulo de forno e mecanismo em PNG transparente' },
    props: { src: `${promptPath}imagem013.png`, alt: 'Objetos intercambiáveis da cozinha temporal' }
  },
  characters: {
    hagia: { src: `${promptPath}imagem1.png`, alt: 'Hagia em leitura narrativa' },
    pio: { src: `${promptPath}imagem014.png`, alt: 'Pio em deslocamento' },
    abuela: { src: `${promptPath}imagem010.png`, alt: 'Abuela como memória visual' }
  },
  panels: {
    continuity: { src: `${promptPath}imagem018.png`, alt: 'Vestígios sobre a mesa' },
    mechanism: { src: `${promptPath}imagem013.png`, alt: 'Mecanismo intercambiável' },
    coexistence: { src: `${promptPath}imagem4.png`, alt: 'Coexistência temporal' },
    versatility: { src: `${promptPath}imagem015.png`, alt: 'Versatilidade do mecanismo' },
    collective: { src: `${promptPath}imagem2.png`, alt: 'Participação coletiva' },
    speed: { src: `${promptPath}hagia1.png`, alt: 'Velocidade narrativa' },
    coordinated: { src: `${promptPath}Calvin-Hobbes.jpg`, alt: 'Ação coordenada' },
    safety: { src: `${promptPath}imagem018.png`, alt: 'Coerência temporal' },
    mechanismsBand: { src: `${promptPath}imagem010.png`, alt: 'Mecanismos conectados' }
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