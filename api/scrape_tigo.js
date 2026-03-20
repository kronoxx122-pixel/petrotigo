const chromium = require("@sparticuz/chromium");
const puppeteerCore = require("puppeteer-core");

module.exports = async (req, res) => {
  // CORS
  res.setHeader("Access-Control-Allow-Origin", "*");
  res.setHeader("Content-Type", "application/json");

  if (req.method === "OPTIONS") {
    return res.status(200).end();
  }

  const { number, type } = req.method === "POST" ? req.body : req.query;
  const phoneNumber = (number || "").replace(/[^0-9]/g, "");
  const searchType = type || "line"; // "line" o "document"

  if (!phoneNumber || phoneNumber.length < 5) {
    return res.status(400).json({ success: false, message: "Número inválido" });
  }

  let browser = null;

  try {
    browser = await puppeteerCore.launch({
      args: chromium.args,
      defaultViewport: { width: 1280, height: 800 },
      executablePath: await chromium.executablePath(),
      headless: chromium.headless,
    });

    const page = await browser.newPage();

    // User-Agent real
    await page.setUserAgent(
      "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36"
    );

    // Navegar al sitio real de Tigo
    await page.goto("https://mi.tigo.com.co/pago-express/facturas", {
      waitUntil: "networkidle2",
      timeout: 20000,
    });

    // Seleccionar tipo de búsqueda
    if (searchType === "line") {
      // Clic en el tab "Línea"
      const tabs = await page.$$('button[class*="tab"], div[class*="tab"]');
      for (const tab of tabs) {
        const text = await page.evaluate((el) => el.textContent, tab);
        if (text && text.toLowerCase().includes("línea")) {
          await tab.click();
          await page.waitForTimeout(500);
          break;
        }
      }
    } else {
      // Clic en tab "Documento"
      const tabs = await page.$$('button[class*="tab"], div[class*="tab"]');
      for (const tab of tabs) {
        const text = await page.evaluate((el) => el.textContent, tab);
        if (text && text.toLowerCase().includes("documento")) {
          await tab.click();
          await page.waitForTimeout(500);
          break;
        }
      }
    }

    // Esperar a que aparezca el campo de entrada
    await page.waitForSelector("input[type='tel'], input[type='text'], input[type='number']", { timeout: 5000 });

    // Buscar y llenar el input visible
    const inputs = await page.$$("input");
    let targetInput = null;
    for (const input of inputs) {
      const isVisible = await page.evaluate((el) => {
        const style = window.getComputedStyle(el);
        return style.display !== "none" && style.visibility !== "hidden" && el.offsetParent !== null;
      }, input);
      const inputType = await page.evaluate((el) => el.type, input);
      if (isVisible && ["tel", "text", "number"].includes(inputType)) {
        targetInput = input;
        break;
      }
    }

    if (!targetInput) {
      throw new Error("No se encontró el campo de entrada en la página de Tigo");
    }

    // Limpiar y escribir el número
    await targetInput.click({ clickCount: 3 });
    await targetInput.type(phoneNumber, { delay: 50 });

    // Buscar y clic en botón CONTINUAR
    await page.waitForTimeout(500);
    const buttons = await page.$$("button");
    let continueBtn = null;
    for (const button of buttons) {
      const text = await page.evaluate((el) => el.textContent, button);
      if (text && text.toLowerCase().includes("continuar")) {
        continueBtn = button;
        break;
      }
    }

    if (!continueBtn) {
      throw new Error("No se encontró el botón CONTINUAR");
    }

    // Interceptar la respuesta de la API de saldo
    const responsePromise = page.waitForResponse(
      (response) =>
        response.url().includes("express/balance") &&
        response.status() === 200,
      { timeout: 15000 }
    );

    await continueBtn.click();

    // Esperar la respuesta de la API
    const apiResponse = await responsePromise;
    const apiData = await apiResponse.json();

    await browser.close();
    browser = null;

    // Retornar los datos crudos de Tigo
    return res.status(200).json({
      success: true,
      source: "puppeteer_scraper",
      tigo_response: apiData,
    });
  } catch (error) {
    if (browser) await browser.close();

    return res.status(500).json({
      success: false,
      message: error.message || "Error al consultar Tigo",
      source: "puppeteer_scraper",
    });
  }
};
