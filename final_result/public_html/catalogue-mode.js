(function () {
  window.catalogueModeDebug = "active";

  const normalizePath = (value) => {
    if (!value) return "/";
    try {
      const path = value.split("?")[0].split("#")[0];
      if (!path) return "/";
      return path.endsWith("/") && path !== "/" ? path.slice(0, -1) : path;
    } catch {
      return "/";
    }
  };

  const BLOCKED_PATHS = new Set(
    [
      "/cart",
      "/checkout",
      "/checkout/success",
      "/payment",
      "/payment/success",
      "/payment/fail",
      "/auth",
      "/auth/callback",
      "/auth/naver/callback",
      "/auth/reset-password",
      "/account",
      "/admin",
    ].map(normalizePath)
  );
  const REDIRECT_TARGET = "/products";
  const CTA_PATTERNS = [
    /제품\s*문의\s*보내기/i,
    /이 제품의 견적을 문의하세요/i,
    /견적\s*문의\s*요청/i,
  ];
  const TEXT_PATTERNS = [
    /장바구니/i,
    /결제/i,
    /주문/i,
    /구매/i,
    /ai\s*상담/i,
    /담기/i,
    /checkout/i,
    /payment/i,
    /order/i,
    /cart/i,
  ];
  const HREF_PATTERNS = [
    /\/cart/i,
    /\/checkout/i,
    /\/payment/i,
  ];
  const CONTACT_EMAIL = "sales@studio-sb.com";
  const CONTACT_PHONE = "02-1234-5678";
  const PRICE_LABEL = "견적 문의";
  const PRICE_DETECT_RE = /(₩\s?[\d.,]+(?:\s?[~\-–]\s?₩?\s?[\d.,]+)?|[\d.,]+\s?원)/;
  const PRICE_REPLACE_RE = new RegExp(PRICE_DETECT_RE.source, "g");
  const IGNORED_CONSOLE_ERROR_PATTERNS = [
    /Product list request failed: TypeError: Failed to fetch/,
    /API request failed: TypeError: Failed to fetch/,
  ];
  const originalConsoleError =
    console && typeof console.error === "function" ? console.error.bind(console) : null;
  if (originalConsoleError) {
    console.error = (...args) => {
      const message = args
        .map((item) => {
          if (typeof item === "string") return item;
          if (item && typeof item.message === "string") return item.message;
          return "";
        })
        .filter(Boolean)
        .join(" ");
      if (
        message &&
        IGNORED_CONSOLE_ERROR_PATTERNS.some((pattern) => pattern.test(message))
      ) {
        return;
      }
      return originalConsoleError(...args);
    };
  }
  const FORCE_LOCAL = true;
  const SUPABASE_HOST_SUFFIX = ".supabase.co";
  const CATEGORY_ID_TO_SLUG = {
    cat_agv: "agv-casters",
    cat_industrial: "industrial-casters",
    cat_polyurethane: "polyurethane-wheels",
    cat_rubber: "rubber-wheels",
  };
  const enforceCataloguePath = () => {
    const current = normalizePath(window.location.pathname);
    if (BLOCKED_PATHS.has(current) && current !== REDIRECT_TARGET) {
      const targetUrl = `${REDIRECT_TARGET}${window.location.search || ""}`;
      window.history.replaceState({}, "", targetUrl);
      queueMicrotask(() => window.dispatchEvent(new PopStateEvent("popstate")));
    }
  };

  const patchHistory = (method) => {
    const original = history[method];
    history[method] = function patchedHistoryState(...args) {
      const result = original.apply(this, args);
      setTimeout(enforceCataloguePath, 0);
      return result;
    };
  };

  const markImage = (img, detail = false) => {
    if (!img || img.dataset.catalogueImage === "1") return;
    img.dataset.catalogueImage = "1";
    img.classList.add("catalogue-product-image");
    if (detail) {
      img.classList.add("catalogue-product-image--detail");
    }
  };

  const neutralizeElement = (el) => {
    if (!el || el.dataset.catalogueHidden === "true") return;
    el.dataset.catalogueHidden = "true";
    el.setAttribute("aria-hidden", "true");
    el.setAttribute("tabindex", "-1");
    if (el.tagName === "A") {
      el.removeAttribute("href");
    }
    el.style.setProperty("display", "none", "important");

    // Removed automatic insertion of contact button for cleaner layout.
  };

  const shouldNeutralize = (el) => {
    if (!el) return false;
    const text = (el.innerText || el.textContent || "").replace(/\s+/g, " ").trim();
    if (text && TEXT_PATTERNS.some((pattern) => pattern.test(text))) return true;

    const aria = (el.getAttribute("aria-label") || "").trim();
    if (aria && TEXT_PATTERNS.some((pattern) => pattern.test(aria))) return true;

    const href = (el.getAttribute("href") || "").trim();
    if (href && HREF_PATTERNS.some((pattern) => pattern.test(href))) return true;

    if (el.dataset && typeof el.dataset.action === "string" && /cart|checkout|order/i.test(el.dataset.action)) {
      return true;
    }

    return false;
  };

  const scanInteractive = (root) => {
    if (!root || !root.querySelectorAll) return;
    root.querySelectorAll("a, button").forEach((el) => {
      if (el.tagName === "BUTTON") {
        try {
          const text = (el.innerText || el.textContent || "").replace(/\s+/g, " ").trim();
          if (text && CTA_PATTERNS.some((pattern) => pattern.test(text))) {
            neutralizeElement(el);
            return;
          }
        } catch {
          // ignore text extraction errors
        }
      }
      if (shouldNeutralize(el)) {
        neutralizeElement(el);
      }
    });
  };

  const scanTextual = (root) => {
    if (!root || !root.querySelectorAll) return;
    root.querySelectorAll("*").forEach((el) => {
      if (el.dataset && el.dataset.catalogueTextChecked === "1") return;
      if (el.tagName === "HTML" || el.tagName === "BODY") return;
      if (el.children && el.children.length > 0) {
        if (el.dataset) el.dataset.catalogueTextChecked = "1";
        return;
      }
      const text = (el.textContent || "").trim();
      if (!text) return;
      if (text.length > 48) {
        if (el.dataset) el.dataset.catalogueTextChecked = "1";
        return;
      }
      if (TEXT_PATTERNS.some((pattern) => pattern.test(text))) {
        neutralizeElement(el);
      }
      if (el.dataset) el.dataset.catalogueTextChecked = "1";
    });
  };

  const showQuoteModal = () => {
    const message = [
      "💼 견적 문의 안내",
      "",
      `📞 전화: ${CONTACT_PHONE}`,
      `📧 이메일: ${CONTACT_EMAIL}`,
    ].join("\n");
    window.alert(message);
  };

  const handleQuoteClick = (event) => {
    event.preventDefault();
    event.stopPropagation();
    showQuoteModal();
  };

  const handleQuoteKeydown = (event) => {
    if (event.key === "Enter" || event.key === " " || event.key === "Spacebar") {
      event.preventDefault();
      showQuoteModal();
    }
  };

  const attachPriceInteraction = (element) => {
    if (!element || element.dataset.cataloguePriceInteractive === "1") return;

    element.dataset.cataloguePriceInteractive = "1";
    element.classList.add("catalogue-price-label");

    if (!/^(A|BUTTON)$/i.test(element.tagName)) {
      if (!element.hasAttribute("role")) element.setAttribute("role", "button");
      if (!element.hasAttribute("tabindex")) element.setAttribute("tabindex", "0");
    }

    if (!element.hasAttribute("aria-label")) {
      element.setAttribute("aria-label", "이 제품의 견적을 문의하세요");
    }

    element.addEventListener("click", handleQuoteClick, { capture: true });
    element.addEventListener("keydown", handleQuoteKeydown);
  };

  const processPriceTextNode = (textNode) => {
    if (!textNode) return;
    const parent = textNode.parentElement;
    if (!parent) return;
    if (parent.closest("script, style, noscript")) return;

    const original = textNode.textContent || "";
    if (!original) return;

    const previous = textNode.__cataloguePriceSnapshot;
    if (previous === original) return;

    if (original.includes(PRICE_LABEL)) {
      textNode.__cataloguePriceSnapshot = original;
      attachPriceInteraction(parent);
      return;
    }

    if (!PRICE_DETECT_RE.test(original)) return;

    const replaced = original.replace(PRICE_REPLACE_RE, PRICE_LABEL);
    if (replaced === original) return;

    textNode.textContent = replaced;
    textNode.__cataloguePriceSnapshot = textNode.textContent || replaced;
    attachPriceInteraction(parent);
  };

  const scanPriceNodes = (root) => {
    if (!root) return;

    if (root.nodeType === Node.TEXT_NODE) {
      processPriceTextNode(root);
      return;
    }

    const context = root.nodeType === Node.DOCUMENT_NODE ? root.body || root : root;
    if (!context) return;

    const doc = root.nodeType === Node.DOCUMENT_NODE ? root : context.ownerDocument || document;
    if (!doc || !doc.createTreeWalker) return;

    const walker = doc.createTreeWalker(context, NodeFilter.SHOW_TEXT, {
      acceptNode(node) {
        if (!node) {
          return NodeFilter.FILTER_REJECT;
        }
        const text = node.textContent || "";
        if (!text) {
          return NodeFilter.FILTER_SKIP;
        }
        if (node.__cataloguePriceSnapshot === text) {
          return NodeFilter.FILTER_SKIP;
        }
        if (text.includes(PRICE_LABEL)) {
          return NodeFilter.FILTER_ACCEPT;
        }
        return PRICE_DETECT_RE.test(text) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_SKIP;
      },
    });

    while (walker.nextNode()) {
      processPriceTextNode(walker.currentNode);
    }
  };

  const tagCatalogMedia = (root) => {
    if (!root || !root.querySelectorAll) return;
    root.querySelectorAll('a[href^="/products/"]').forEach((card) => {
      card.classList.add("catalogue-product-card");
      card.querySelectorAll("img").forEach((img) => markImage(img));
      const container = card.parentElement;
      if (container && !container.dataset.catalogueGrid && container.querySelectorAll('a[href^="/products/"]').length >= 3) {
        container.classList.add("catalogue-layout-grid");
        container.dataset.catalogueGrid = "1";
      }

      const targetHref = card.getAttribute("href");
      if (!targetHref) {
        return;
      }

      let clickable = card.parentElement;
      const isInteractive = (element) =>
        element && (
          element.tagName === "A" ||
          element.tagName === "BUTTON" ||
          element.hasAttribute("role")
        );

      let wrapper = card;
      while (wrapper && wrapper !== document.body) {
        if (wrapper !== card && wrapper.classList && wrapper.classList.contains("group")) {
          clickable = wrapper;
          break;
        }
        wrapper = wrapper.parentElement;
      }

      if (!clickable) {
        clickable = card;
      }

      if (!clickable.dataset.catalogueCardBound) {
        clickable.dataset.catalogueCardBound = "1";
        if (!clickable.hasAttribute("tabindex")) {
          clickable.setAttribute("tabindex", "0");
        }
        if (!clickable.hasAttribute("role")) {
          clickable.setAttribute("role", "link");
        }
        if (!clickable.style.cursor) {
          clickable.style.cursor = "pointer";
        }

        const handleActivate = () => {
          window.location.href = targetHref;
        };

        clickable.addEventListener("click", (event) => {
          if (event.defaultPrevented) return;
          if (event.target && isInteractive(event.target.closest("a, button, input, textarea, select, label"))) {
            return;
          }
          handleActivate();
        });

        clickable.addEventListener("keydown", (event) => {
          if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            handleActivate();
          }
        });
      }
    });

    if (window.location.pathname.startsWith("/products/")) {
      document.querySelectorAll("main img").forEach((img) => {
        const src = (img.getAttribute("src") || "").trim();
        if (!src || src.startsWith("data:")) return;
        if (src.includes("/images/") || src.includes("/assets/")) {
          markImage(img, true);
        }
      });
    }
  };

  const MOCKABLE_PATHS = new Set([
    "/api/cart/list.php",
  ]);

  const FALLBACK_JSON = {
    "/api/cart/list.php": () =>
      new Response(
        JSON.stringify({
          success: true,
          items: [],
          total_amount: 0,
          items_count: 0,
        }),
        {
          status: 200,
          headers: { "Content-Type": "application/json; charset=utf-8" },
        }
      ),
  };

  const resolveFallback = (pathname) => {
    if (pathname === "/api/products.php") return "/data/products.json";
    if (pathname === "/api/categories.php") return "/data/categories.json";
    return null;
  };

  const attachFetchMock = () => {
    if (!window.fetch || window.__CATALOGUE_FETCH_PATCHED__) return;

    const originalFetch = window.fetch.bind(window);

    window.fetch = async (input, init) => {
      try {
        const request =
          typeof input === "string" ? new Request(input, init) : input;
        const url = new URL(request.url, window.location.href);

        // Prefer dynamic API over static JSON fallbacks to avoid stale data/images
        if (url.origin === window.location.origin && url.pathname === "/data/products.json") {
          try {
            const apiUrl = new URL(`/api/products/list.php?limit=500`, window.location.origin).toString();
            const res = await originalFetch(new Request(apiUrl, { cache: "no-cache" }));
            const json = await res.json();
            const arr = Array.isArray(json?.products) ? json.products : (Array.isArray(json?.data) ? json.data : []);
            return new Response(JSON.stringify(arr), { status: 200, headers: { "Content-Type": "application/json; charset=utf-8" } });
          } catch (e) {
            console.warn("[CatalogueMode] products.json reroute failed", e);
          }
        }
        if (url.origin === window.location.origin && url.pathname === "/data/categories.json") {
          try {
            const apiUrl = new URL(`/api/categories/list.php`, window.location.origin).toString();
            const res = await originalFetch(new Request(apiUrl, { cache: "no-cache" }));
            const json = await res.json();
            const arr = Array.isArray(json?.categories) ? json.categories : (Array.isArray(json?.data) ? json.data : []);
            return new Response(JSON.stringify(arr), { status: 200, headers: { "Content-Type": "application/json; charset=utf-8" } });
          } catch (e) {
            console.warn("[CatalogueMode] categories.json reroute failed", e);
          }
        }

        if (
          (url.hostname === "localhost" || url.hostname === "127.0.0.1") &&
          url.port === "8083" &&
          url.pathname.startsWith("/api/")
        ) {
          try {
            const proxyUrl = new URL(url.pathname + url.search + url.hash, window.location.origin).toString();
            const init = {
              method: request.method,
              headers: new Headers(request.headers),
              credentials: request.credentials,
              cache: request.cache,
              redirect: request.redirect,
              referrer: request.referrer,
              referrerPolicy: request.referrerPolicy,
              integrity: request.integrity,
              keepalive: request.keepalive,
              mode: request.mode === "cors" ? "same-origin" : request.mode,
              signal: request.signal,
            };

            // GET/HEAD do not carry a body; other methods are not expected here.
            if (!["GET", "HEAD"].includes((request.method || "GET").toUpperCase()) && !request.bodyUsed) {
              init.body = request.body;
            }

            return await originalFetch(proxyUrl, init);
          } catch (proxyError) {
            console.warn("[CatalogueMode] Local API proxy failed", proxyError);
          }
        }

        const performLocalCatalogueFallback = async (supabaseResponse = null, supabaseError = null) => {
          try {
            const params = url.searchParams;
            let categorySlug = null;
            let productSlugFilter = null;
            let productIdFilter = null;
            let searchParam = params.get("search");
            if (searchParam) {
              searchParam = searchParam.replace(/^"(.+)"$/, "$1");
            }

            params.forEach((value, key) => {
              const lowerKey = key.toLowerCase();
              if (lowerKey.includes("category") || lowerKey.includes("slug")) {
                const slugMatch = value.match(/eq\.([^,]+)/);
                if (slugMatch && slugMatch[1]) {
                  categorySlug = slugMatch[1].replace(/^"(.+)"$/, "$1");
                  return;
                }
              }

              if (lowerKey.includes("category") || lowerKey.includes("category_id")) {
                const idMatch = value.match(/eq\.(cat_[a-z_]+)/);
                if (idMatch && CATEGORY_ID_TO_SLUG[idMatch[1]]) {
                  categorySlug = CATEGORY_ID_TO_SLUG[idMatch[1]];
                }
              }

              if (lowerKey.includes("slug") && productSlugFilter === null) {
                const slugMatch = value.match(/eq\.([^,]+)/);
                if (slugMatch && slugMatch[1]) {
                  productSlugFilter = slugMatch[1].replace(/^"(.+)"$/, "$1");
                }
              }

              if (lowerKey === "id" && productIdFilter === null) {
                const idMatch = value.match(/eq\.([^,]+)/);
                if (idMatch && idMatch[1]) {
                  productIdFilter = idMatch[1].replace(/^"(.+)"$/, "$1");
                }
              }
            });

            const orParam = params.get("or");
            if (orParam) {
              const decodedOr = orParam;
              if (!productSlugFilter) {
                const slugMatch = decodedOr.match(/slug\.eq\.([^,)]+)/i);
                if (slugMatch && slugMatch[1]) {
                  productSlugFilter = slugMatch[1].replace(/^"(.+)"$/, "$1");
                }
              }
              if (!productIdFilter) {
                const idMatch = decodedOr.match(/id\.eq\.([^,)]+)/i);
                if (idMatch && idMatch[1]) {
                  productIdFilter = idMatch[1].replace(/^"(.+)"$/, "$1");
                }
              }
            }

            if (productSlugFilter || productIdFilter) {
              let fallbackUrl = "/api/products/get.php";
              if (productSlugFilter) {
                fallbackUrl += `?slug=${encodeURIComponent(productSlugFilter)}`;
              } else if (productIdFilter) {
                fallbackUrl += `?id=${encodeURIComponent(productIdFilter)}`;
              }

              const fallbackResponse = await originalFetch(new Request(fallbackUrl, { cache: "no-cache" }));
              if (!fallbackResponse.ok) {
                throw new Error(`Fallback request failed (${fallbackResponse.status})`);
              }

              const fallbackJson = await fallbackResponse.json();
              const product = fallbackJson?.product ?? fallbackJson?.data ?? null;
              const payload = product ? [product] : [];
              const total = payload.length;

              return new Response(JSON.stringify(payload), {
                status: 200,
                headers: {
                  "Content-Type": "application/json; charset=utf-8",
                  "Content-Range": total ? `0-${total - 1}/${total}` : "0-0/0",
                },
              });
            }

            let fallbackUrl = "/api/products/list.php";
            const requestedLimit = parseInt(params.get("limit") || "", 10);
            const limit = Number.isFinite(requestedLimit) && requestedLimit > 0 ? Math.min(requestedLimit, 500) : 500;

            const queryParts = [`limit=${limit}`];

            if (categorySlug) {
              queryParts.push(`category=${encodeURIComponent(categorySlug)}`);
            }

            if (params.has("offset")) {
              const offset = parseInt(params.get("offset") || "", 10);
              if (Number.isFinite(offset) && offset >= 0) {
                queryParts.push(`page=${Math.floor(offset / limit) + 1}`);
              }
            }

            if (searchParam) {
              queryParts.push(`search=${encodeURIComponent(searchParam)}`);
            }

            fallbackUrl += `?${queryParts.join("&")}`;

            const fallbackResponse = await originalFetch(new Request(fallbackUrl, { cache: "no-cache" }));
            if (!fallbackResponse.ok) {
              throw new Error(`Fallback request failed (${fallbackResponse.status})`);
            }
            const fallbackJson = await fallbackResponse.json();

            const products = Array.isArray(fallbackJson?.products)
              ? fallbackJson.products
              : Array.isArray(fallbackJson?.data)
              ? fallbackJson.data
              : [];
            const total = products.length;
            const contentRange = total > 0 ? `0-${total - 1}/${total}` : `0-0/0`;

            return new Response(JSON.stringify(products), {
              status: 200,
              headers: {
                "Content-Type": "application/json; charset=utf-8",
                "Content-Range": contentRange,
              },
            });
          } catch (supabaseFallbackError) {
            console.warn("[CatalogueMode] Supabase fallback error", supabaseFallbackError);
            if (supabaseResponse) {
              return supabaseResponse;
            }
            if (supabaseError) {
              throw supabaseError;
            }
          }
        };

        if (url.hostname.endsWith(SUPABASE_HOST_SUFFIX) && url.pathname.includes("/rest/v1/products")) {
          if (FORCE_LOCAL) {
            return await performLocalCatalogueFallback();
          }

          let supabaseResponse = null;
          let supabaseError = null;

          try {
            supabaseResponse = await originalFetch(request);
            if (supabaseResponse && supabaseResponse.ok) {
              return supabaseResponse;
            }
            if (supabaseResponse && supabaseResponse.status >= 400 && supabaseResponse.status < 500) {
              return supabaseResponse;
            }
          } catch (error) {
            supabaseError = error;
          }

          return await performLocalCatalogueFallback(supabaseResponse, supabaseError);
        }

        const isLocalLoopback = url.hostname === "localhost" || url.hostname === "127.0.0.1";
        const currentOrigin = window.location.origin;
        if (
          isLocalLoopback &&
          url.port &&
          url.port !== window.location.port
        ) {
          try {
            const localUrl = new URL(url.pathname + url.search + url.hash, currentOrigin).toString();
            const localRequest = new Request(localUrl, request);
            return await originalFetch(localRequest);
          } catch (rerouteError) {
            console.warn("[CatalogueMode] Local reroute failed", rerouteError);
          }
        }

        if (
          url.origin === window.location.origin &&
          MOCKABLE_PATHS.has(url.pathname)
        ) {
          try {
            const response = await originalFetch(request);
            const contentType = response.headers
              .get("content-type")
              ?.toLowerCase();
            if (response.ok && contentType?.includes("application/json")) {
              return response;
            }
          } catch (networkError) {
            console.warn(
              "[CatalogueMode] Network error for",
              url.pathname,
              networkError
            );
          }

          if (FALLBACK_JSON[url.pathname]) {
            return FALLBACK_JSON[url.pathname]();
          }

          const fallbackUrl = resolveFallback(url.pathname);
          if (fallbackUrl) {
            return originalFetch(new Request(fallbackUrl, { cache: "no-cache" }));
          }
        }

        return originalFetch(request);
      } catch (error) {
        return originalFetch(input, init);
      }
    };

    window.__CATALOGUE_FETCH_PATCHED__ = true;
  };

  const guardian = () => {
    scanInteractive(document);
    scanTextual(document);
    tagCatalogMedia(document);
    scanPriceNodes(document);
    hideInvalidDownloadsAndNoise(document);
    ensureDetailHeadings(document);
  };

  attachFetchMock();

  const initCatalogueMode = () => {
    document.body.classList.add("catalogue-mode");
    attachFetchMock();
    guardian();

    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.type === "childList") {
          mutation.addedNodes.forEach((node) => {
            scanPriceNodes(node);
            if (node.nodeType !== Node.ELEMENT_NODE) return;
            scanInteractive(node);
            scanTextual(node);
            tagCatalogMedia(node);
          });
        } else if (mutation.type === "characterData") {
          scanPriceNodes(mutation.target);
        } else if (mutation.type === "attributes" && mutation.target instanceof HTMLElement) {
          if (mutation.attributeName === "href" || mutation.attributeName === "aria-label") {
            scanInteractive(mutation.target);
            scanTextual(mutation.target);
          }
          scanPriceNodes(mutation.target);
        }
      });
    });

    observer.observe(document.documentElement, {
      childList: true,
      subtree: true,
      attributes: true,
      characterData: true,
      attributeFilter: ["href", "aria-label", "data-action"],
    });

    document.addEventListener(
      "submit",
      (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        const text = (form.textContent || "").replace(/\s+/g, " ").trim();
        if (TEXT_PATTERNS.some((pattern) => pattern.test(text))) {
          event.preventDefault();
          event.stopPropagation();
        }
      },
      true
    );

    document.addEventListener(
      "click",
      (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        if (shouldNeutralize(target)) {
          event.preventDefault();
          event.stopPropagation();
        }
      },
      true
    );

    window.__CATALOGUE_MODE__ = true;
  };

  enforceCataloguePath();
  patchHistory("pushState");
  patchHistory("replaceState");
  window.addEventListener("popstate", enforceCataloguePath);

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initCatalogueMode, { once: true });
  } else {
    initCatalogueMode();
  }

  setTimeout(guardian, 800);
  setTimeout(guardian, 2000);
  setTimeout(guardian, 4000);
})();

// Hide invalid download buttons and noisy labels like "Image 2", "thumbnail 1"
(function(){
  function hideInvalidDownloadsAndNoise(root){
    try{
      const doc = root.ownerDocument || document;
      // 자료 다운로드 / Download buttons with missing/placeholder href
      doc.querySelectorAll('a, button').forEach((el)=>{
        const txt = (el.innerText||el.textContent||'').trim().toLowerCase();
        if(/자료\s*다운로드|download/i.test(txt)){
          const href = (el.getAttribute('href')||'').trim();
          const hasValid = /\.(pdf|zip|stp|step|igs|iges|dwg|dxf)(\?|$)/i.test(href) || href.startsWith('http');
          if(!hasValid){ el.style.setProperty('display','none','important'); el.setAttribute('aria-hidden','true'); }
        }
      });
      // Remove noisy product labels
      doc.querySelectorAll('p, li, span, div').forEach((el)=>{
        const t=(el.innerText||'').trim();
        if(/^image\s*\d+$/i.test(t) || /^.*thumbnail\s*\d+$/i.test(t)){
          el.style.setProperty('display','none','important');
        }
      });
    }catch(_){/* noop */}
  }
  window.hideInvalidDownloadsAndNoise = hideInvalidDownloadsAndNoise;
})();

// Ensure consistent headings on product detail pages
(function(){
  function ensureDetailHeadings(root){
    try{
      const doc = root.ownerDocument || document;
      if(!location.pathname.startsWith('/products/')) return;
      // If there's only one spec section, try injecting a Features heading above any bullet list
      const main = doc.querySelector('main') || doc.body;
      if(!main) return;
      const headings = Array.from(main.querySelectorAll('h2,h3')).map(h=>h.innerText.trim());
      const hasFeatures = headings.some(t=>/주요\s*특징|features/i.test(t));
      const hasSpecs = headings.some(t=>/세부\s*사양|spec/i.test(t));
      if(!hasFeatures){
        const bullets = main.querySelector('ul,ol');
        if(bullets && bullets.parentElement){
          const h = doc.createElement('h2');
          h.className = 'catalogue-section';
          h.textContent = '주요 특징';
          bullets.parentElement.insertBefore(h, bullets);
        }
      }
      if(!hasSpecs){
        const table = main.querySelector('table');
        if(table && table.parentElement){
          const h = doc.createElement('h2');
          h.className = 'catalogue-section';
          h.textContent = '세부 사양';
          table.parentElement.insertBefore(h, table);
        }
      }
    }catch(_){/* noop */}
  }
  window.ensureDetailHeadings = ensureDetailHeadings;
})();
