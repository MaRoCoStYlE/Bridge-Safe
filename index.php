const express = require('express');
const axios = require('axios');
const cors = require('cors');
const helmet = require('helmet');
const { RateLimiterMemory } = require('rate-limiter-flexible');

const app = express();
app.use(helmet());
app.use(cors({ origin: false })); // bloque tout referer
app.use(express.json());

const rateLimiter = new RateLimiterMemory({
  keyPrefix: 'api',
  points: 10,
  duration: 60,
});

const STORE_B_DOMAIN = 'https://merveille.store';
const STORE_B_CART_ADD = `${STORE_B_DOMAIN}/cart/add.js`;

app.post('/create-checkout', async (req, res) => {
  try {
    await rateLimiter.consume(req.ip);

    const { items } = req.body; // [{ id: 123456789, quantity: 1 }, ...]

    if (!items || !Array.isArray(items)) return res.status(400).send('Invalid items');

    const formData = new URLSearchParams();
    items.forEach(item => {
      formData.append('id[]', item.id);
      formData.append('quantity[]', item.quantity);
    });

    const response = await axios.post(STORE_B_CART_ADD, formData.toString(), {
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'User-Agent': 'Mozilla/5.0 (compatible; AgentFlow/1.0)',
      },
      maxRedirects: 0,
      validateStatus: (status) => status >= 200 && status < 400,
    });

    const redirectUrl = response.headers.location || `${STORE_B_DOMAIN}/checkout`;
    res.redirect(303, redirectUrl);
  } catch (err) {
    console.error(err);
    res.status(429).send('Rate limited or error');
  }
});

app.listen(process.env.PORT || 3000, () => {
  console.log('AgentFlow bridge running');
});
